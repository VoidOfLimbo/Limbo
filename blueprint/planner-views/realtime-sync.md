# Planner Views — Real-Time Sync Strategy

**Depends on:** [`blueprint/planner-views/graphql-schema.md`](./graphql-schema.md)
**Status:** Blueprint / Design (Phase 3)

---

## Overview

Real-time sync enables multiple sessions (browser tabs, devices) to see planner changes instantly, with no page refresh. The architecture mirrors GitHub Projects' approach:

1. **Optimistic UI** — apply changes locally before the server responds
2. **WebSocket broadcast** — server broadcasts confirmed changes to all listeners
3. **Rollback on failure** — if the server rejects a mutation, revert local state

---

## Stack

| Layer | Technology |
|---|---|
| Backend broadcast | Laravel Echo Server (Soketi or Pusher) |
| Backend dispatch | Laravel `ShouldBroadcast` events |
| Frontend listener | `laravel-echo` + `pusher-js` |
| GraphQL subscriptions | Lighthouse subscriptions via Echo |
| Optimistic state | Pinia store with `pendingMutations` map |

---

## Backend — Broadcast Events

### Channel Design

All planner events for a user broadcast on a **private channel** keyed by user ID:

```
private-planner.{userId}
```

For shared milestones (Phase 6), the channel will expand to include collaborators — but for now, one channel per user.

### Laravel Broadcast Events

```php
// app/Events/PlannerItemUpdated.php
class PlannerItemUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $action,    // 'created' | 'updated' | 'deleted'
        public readonly ?Event $event,
        public readonly ?string $eventId,  // for deleted (model gone)
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("planner.{$this->user->id}")];
    }

    public function broadcastAs(): string
    {
        return 'planner.item.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action'  => $this->action,
            'eventId' => $this->eventId ?? $this->event?->id,
            'event'   => $this->event ? new EventResource($this->event) : null,
        ];
    }
}
```

```php
// app/Events/PlannerFieldValueUpdated.php
class PlannerFieldValueUpdated implements ShouldBroadcast
{
    public function __construct(
        public readonly User $user,
        public readonly PlannerFieldValue $fieldValue,
        public readonly string $eventId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("planner.{$this->user->id}")];
    }

    public function broadcastAs(): string
    {
        return 'planner.fieldvalue.updated';
    }
}
```

### Dispatching from Observers

```php
// app/Observers/EventObserver.php
class EventObserver
{
    public function created(Event $event): void
    {
        PlannerItemUpdated::dispatch($event->user, 'created', $event, $event->id);
    }

    public function updated(Event $event): void
    {
        PlannerItemUpdated::dispatch($event->user, 'updated', $event, $event->id);
    }

    public function deleted(Event $event): void
    {
        PlannerItemUpdated::dispatch($event->user, 'deleted', null, $event->id);
    }
}
```

Register in `AppServiceProvider`:
```php
Event::observe(EventObserver::class);
```

### Channel Authorization

```php
// routes/channels.php
Broadcast::channel('planner.{userId}', function (User $user, string $userId) {
    return $user->id === $userId;
});
```

---

## Frontend — Echo Listener Composable

```typescript
// resources/js/composables/usePlannerRealtime.ts

import Echo from 'laravel-echo'
import { usePlannerStore } from '@/stores/planner'
import { useAuth } from '@/composables/useAuth'

export function usePlannerRealtime() {
    const store = usePlannerStore()
    const { user } = useAuth()

    function subscribe() {
        window.Echo
            .private(`planner.${user.value.id}`)
            .listen('.planner.item.updated', (payload: PlannerItemPayload) => {
                handleItemUpdate(payload)
            })
            .listen('.planner.fieldvalue.updated', (payload: FieldValuePayload) => {
                handleFieldValueUpdate(payload)
            })
    }

    function unsubscribe() {
        window.Echo.leave(`planner.${user.value.id}`)
    }

    function handleItemUpdate(payload: PlannerItemPayload) {
        if (payload.action === 'created') {
            store.insertItem(payload.event!)
        } else if (payload.action === 'updated') {
            // Ignore if we originated this update (optimistic already applied)
            if (store.hasPending(payload.eventId)) { return }
            store.mergeItem(payload.event!)
        } else if (payload.action === 'deleted') {
            store.removeItem(payload.eventId)
        }
    }

    function handleFieldValueUpdate(payload: FieldValuePayload) {
        if (store.hasPending(payload.eventId)) { return }
        store.mergeFieldValue(payload.fieldValue)
    }

    return { subscribe, unsubscribe }
}
```

---

## Optimistic Update Pattern

### How it works

```
User clicks "Mark Complete"
    │
    ├── 1. Generate a localMutationId (UUID)
    ├── 2. Store previous state in pendingMutations map
    ├── 3. Apply change to Pinia store immediately (UI updates)
    ├── 4. Send GraphQL mutation to server
    │
    ├── Server OK ──→ Remove from pendingMutations (done)
    │
    └── Server Error → Rollback from pendingMutations snapshot + show toast
```

### Pinia Store Structure

```typescript
// resources/js/stores/planner.ts
import { defineStore } from 'pinia'

interface PendingMutation {
    itemId: string
    snapshot: PlannerItem     // deep clone of item before mutation
    timestamp: number
}

export const usePlannerStore = defineStore('planner', {
    state: () => ({
        items: [] as PlannerItem[],
        fieldValues: {} as Record<string, Record<string, FieldValue>>,  // [itemId][fieldId]
        pendingMutations: new Map<string, PendingMutation>(),
        activeView: null as PlannerView | null,
        activeMilestoneId: null as string | null,
        filters: [] as FilterRule[],
        sorts: [] as SortRule[],
        groupBy: null as GroupByConfig | null,
        selectedItemIds: new Set<string>(),
        fieldSchema: [] as PlannerField[],
    }),

    actions: {
        hasPending(itemId: string): boolean {
            return [...this.pendingMutations.values()].some(m => m.itemId === itemId)
        },

        applyOptimistic(mutationId: string, itemId: string, patch: Partial<PlannerItem>) {
            const item = this.items.find(i => i.id === itemId)
            if (!item) { return }

            // Save snapshot before mutating
            this.pendingMutations.set(mutationId, {
                itemId,
                snapshot: structuredClone(item),
                timestamp: Date.now(),
            })

            // Apply patch
            Object.assign(item, patch)
        },

        confirmOptimistic(mutationId: string) {
            this.pendingMutations.delete(mutationId)
        },

        rollbackOptimistic(mutationId: string) {
            const pending = this.pendingMutations.get(mutationId)
            if (!pending) { return }

            const idx = this.items.findIndex(i => i.id === pending.itemId)
            if (idx !== -1) {
                this.items[idx] = pending.snapshot
            }
            this.pendingMutations.delete(mutationId)
        },
    },
})
```

### useOptimisticUpdate Composable

```typescript
// resources/js/composables/useOptimisticUpdate.ts
import { v4 as uuidv4 } from 'uuid'
import { usePlannerStore } from '@/stores/planner'

export function useOptimisticUpdate() {
    const store = usePlannerStore()

    async function mutate<T>(
        itemId: string,
        patch: Partial<PlannerItem>,
        mutation: () => Promise<T>,
        onError?: (err: unknown) => void,
    ): Promise<T | null> {
        const mutationId = uuidv4()

        // 1. Apply optimistically
        store.applyOptimistic(mutationId, itemId, patch)

        try {
            // 2. Execute server mutation
            const result = await mutation()
            store.confirmOptimistic(mutationId)
            return result
        } catch (err) {
            // 3. Rollback
            store.rollbackOptimistic(mutationId)
            onError?.(err)
            return null
        }
    }

    return { mutate }
}
```

### Usage in a Component

```typescript
// In PlannerFieldCell.vue or PlannerBoardCard.vue
const { mutate } = useOptimisticUpdate()

async function updateStatus(newStatus: EventStatus) {
    await mutate(
        event.id,
        { status: newStatus },           // optimistic patch
        () => updateEventMutation({      // actual GraphQL call
            id: event.id,
            input: { status: newStatus }
        }),
        () => toast.error('Failed to update status — reverted'),
    )
}
```

---

## Stale Data Handling

**Stale time:** Items fetched via TanStack Query are considered fresh for 30 seconds. After that, a background refetch runs automatically.

**WebSocket as invalidation signal:** When a broadcast event arrives for an item that is *not* in a pending mutation state, the store merges the server state — overriding anything stale.

**Conflict resolution:** Last-writer-wins. The server's persisted state always wins on the next sync. No CRDT or OT — the planner doesn't require concurrent-edit merging at character level.

---

## Soketi Setup (Dev)

```yaml
# compose.yaml (Sail)
soketi:
    image: 'quay.io/soketi/soketi:latest-16-alpine'
    environment:
        SOKETI_DEBUG: '1'
        SOKETI_METRICS_SERVER_ENABLED: '0'
    ports:
        - '${SOKETI_PORT:-6001}:6001'
        - '${SOKETI_METRICS_PORT:-9601}:9601'
    networks:
        - sail
```

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=app-id
PUSHER_APP_KEY=app-key
PUSHER_APP_SECRET=app-secret
PUSHER_HOST=soketi
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1
```

```typescript
// resources/js/echo.ts
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher
window.Echo = new Echo({
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    wsHost: import.meta.env.VITE_PUSHER_HOST ?? 'localhost',
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 6001,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
})
```

---

## Error States

| Scenario | Behaviour |
|---|---|
| Mutation fails (validation) | Rollback + toast with server error message |
| WebSocket disconnected | Polling fallback every 10s via TanStack Query `refetchInterval` |
| Stale snapshot (>5 min pending) | Auto-expire stale pendingMutations, re-fetch from server |
| Concurrent edits | Last-write-wins; server state wins on next Echo event |

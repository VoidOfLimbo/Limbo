<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetFooter,
    SheetClose,
} from '@/components/ui/sheet'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { store, update } from '@/actions/App/Http/Controllers/Planner/EventController'
import { store as storeTag } from '@/actions/App/Http/Controllers/Planner/TagController'
import PlannerTagInput from '@/components/planner/PlannerTagInput.vue'
import type { PlannerEvent, PlannerMilestone, PlannerTag } from '@/types/planner'

const props = defineProps<{
    open: boolean
    event?: PlannerEvent | null
    milestones: PlannerMilestone[]
    tags: PlannerTag[]
    defaultMilestoneId?: string | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    saved: []
}>()

const isEdit = () => !!props.event

// Local tags list (extended when a new tag is created inline)
const localTags = ref<PlannerTag[]>([...props.tags])
watch(() => props.tags, (t) => { localTags.value = [...t] }, { deep: true })

const form = reactive({
    title: '',
    description: '',
    type: 'event',
    status: 'upcoming',
    priority: 'medium',
    milestone_id: 'none' as string,
    start_at: '',
    end_at: '',
    is_all_day: false,
    location: '',
    visibility: 'private',
    tag_ids: [] as string[],
})

const errors = reactive<Record<string, string>>({})
let processing = false

// Populate form when editing
watch(
    () => props.event,
    (evt) => {
        if (evt) {
            form.title = evt.title
            form.description = evt.description ?? ''
            form.type = evt.type
            form.status = evt.status
            form.priority = evt.priority
            form.milestone_id = evt.milestone_id ?? 'none'
            form.start_at = evt.start_at ? evt.start_at.slice(0, 16) : ''
            form.end_at = evt.end_at ? evt.end_at.slice(0, 16) : ''
            form.is_all_day = evt.is_all_day
            form.location = evt.location ?? ''
            form.visibility = evt.visibility
            form.tag_ids = evt.tags.map((t) => t.id)
        } else {
            form.title = ''
            form.description = ''
            form.type = 'event'
            form.status = 'upcoming'
            form.priority = 'medium'
            form.milestone_id = props.defaultMilestoneId ?? 'none'
            form.start_at = ''
            form.end_at = ''
            form.is_all_day = false
            form.location = ''
            form.visibility = 'private'
            form.tag_ids = []
        }
        Object.keys(errors).forEach((k) => delete errors[k])
    },
    { immediate: true },
)

function submit() {
    processing = true
    Object.keys(errors).forEach((k) => delete errors[k])

    const payload: Record<string, unknown> = {
        title: form.title,
        description: form.description || undefined,
        type: form.type,
        status: form.status,
        priority: form.priority,
        milestone_id: form.milestone_id === 'none' ? undefined : form.milestone_id,
        start_at: form.start_at || undefined,
        end_at: form.end_at || undefined,
        is_all_day: form.is_all_day,
        location: form.location || undefined,
        visibility: form.visibility,
        tag_ids: form.tag_ids,
    }

    const definition = isEdit()
        ? update(props.event!.id)
        : store()

    router.visit(definition.url, {
        method: definition.method,
        data: payload,
        preserveScroll: true,
        only: ['events'],
        onError: (errs) => Object.assign(errors, errs),
        onSuccess: () => {
            emit('update:open', false)
            emit('saved')
        },
        onFinish: () => { processing = false },
    })
}
</script>

<template>
    <Sheet :open="open" @update:open="emit('update:open', $event)">
        <SheetContent side="right" class="w-full sm:max-w-[480px] overflow-y-auto flex flex-col gap-0 p-0">
            <SheetHeader class="px-6 py-4 border-b border-border shrink-0">
                <SheetTitle>{{ isEdit() ? 'Edit event' : 'New event' }}</SheetTitle>
                <SheetDescription class="sr-only">{{ isEdit() ? 'Edit event details' : 'Create a new event' }}</SheetDescription>
            </SheetHeader>

            <form class="flex flex-col gap-5 px-6 py-5 flex-1 overflow-y-auto" @submit.prevent="submit">
                <!-- Title -->
                <div class="space-y-1.5">
                    <Label for="evt-title">Title <span class="text-destructive">*</span></Label>
                    <Input id="evt-title" v-model="form.title" placeholder="What needs to happen?" autofocus />
                    <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
                </div>

                <!-- Description -->
                <div class="space-y-1.5">
                    <Label for="evt-desc">Description</Label>
                    <textarea
                        id="evt-desc"
                        v-model="form.description"
                        rows="3"
                        placeholder="Optional details…"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
                    />
                </div>

                <!-- Type / Status / Priority -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="space-y-1.5">
                        <Label>Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="event">Event</SelectItem>
                                <SelectItem value="task">Task</SelectItem>
                                <SelectItem value="milestone_marker">Marker</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="upcoming">Upcoming</SelectItem>
                                <SelectItem value="in_progress">In Progress</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                                <SelectItem value="skipped">Skipped</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="errors.status" class="text-xs text-destructive">{{ errors.status }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Priority</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="critical">Critical</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Milestone -->
                <div class="space-y-1.5">
                    <Label>Milestone</Label>
                    <Select v-model="form.milestone_id">
                        <SelectTrigger class="h-8 text-xs"><SelectValue placeholder="Backlog (no milestone)" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">Backlog (no milestone)</SelectItem>
                            <SelectItem v-for="m in milestones" :key="m.id" :value="m.id">{{ m.title }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Dates -->
                <div class="space-y-1.5">
                    <div class="flex items-center gap-3">
                        <Label class="shrink-0">All day</Label>
                        <input v-model="form.is_all_day" type="checkbox" class="rounded" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <Label for="evt-start" class="text-xs">Start</Label>
                            <input
                                id="evt-start"
                                v-model="form.start_at"
                                :type="form.is_all_day ? 'date' : 'datetime-local'"
                                class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            <p v-if="errors.start_at" class="text-xs text-destructive">{{ errors.start_at }}</p>
                        </div>
                        <div class="space-y-1">
                            <Label for="evt-end" class="text-xs">End</Label>
                            <input
                                id="evt-end"
                                v-model="form.end_at"
                                :type="form.is_all_day ? 'date' : 'datetime-local'"
                                class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                            />
                            <p v-if="errors.end_at" class="text-xs text-destructive">{{ errors.end_at }}</p>
                        </div>
                    </div>
                </div>

                <!-- Location -->
                <div class="space-y-1.5">
                    <Label for="evt-location">Location</Label>
                    <Input id="evt-location" v-model="form.location" placeholder="Optional location" class="h-8 text-sm" />
                </div>

                <!-- Tags -->
                <div class="space-y-1.5">
                    <Label>Tags</Label>
                    <PlannerTagInput
                        v-model="form.tag_ids"
                        :tags="localTags"
                        @create="async (name) => {
                            const def = storeTag()
                            const res = await fetch(def.url, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '' },
                                body: JSON.stringify({ name }),
                            })
                            if (res.ok) {
                                const tag = await res.json()
                                localTags.value = [...localTags.value, tag]
                                form.tag_ids = [...form.tag_ids, tag.id]
                            }
                        }"
                    />
                </div>

                <!-- Visibility -->
                <div class="space-y-1.5">
                    <Label>Visibility</Label>
                    <Select v-model="form.visibility">
                        <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="private">Private</SelectItem>
                            <SelectItem value="shared">Shared</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </form>

            <SheetFooter class="px-6 py-4 border-t border-border shrink-0">
                <SheetClose as-child>
                    <Button variant="outline" type="button">Cancel</Button>
                </SheetClose>
                <Button type="button" :disabled="processing || !form.title.trim()" @click="submit">
                    {{ isEdit() ? 'Save changes' : 'Create event' }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>

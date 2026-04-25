<script setup lang="ts">
import { reactive, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { X } from 'lucide-vue-next'
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
    DrawerFooter,
    DrawerClose,
} from '@/components/ui/drawer'
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
    <Drawer :open="open" direction="bottom" @update:open="emit('update:open', $event)">
        <DrawerContent class="h-[95vh] flex flex-col gap-0 p-0 overflow-hidden">
            <DrawerHeader class="flex-row items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <DrawerTitle class="text-base">{{ isEdit() ? 'Edit event' : 'New event' }}</DrawerTitle>
                <DrawerDescription class="sr-only">{{ isEdit() ? 'Edit event details' : 'Create a new event' }}</DrawerDescription>
                <DrawerClose as-child>
                    <Button variant="ghost" size="icon" class="size-8 shrink-0 text-muted-foreground hover:text-foreground">
                        <X class="size-4" />
                    </Button>
                </DrawerClose>
            </DrawerHeader>

            <form class="flex flex-col gap-5 px-6 py-5 flex-1 min-h-0 overflow-y-auto" @submit.prevent="submit">

                <!-- Row 1: Title + Description -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="evt-title">Title <span class="text-destructive">*</span></Label>
                        <Input id="evt-title" v-model="form.title" placeholder="What needs to happen?" autofocus />
                        <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="evt-desc">Description</Label>
                        <textarea
                            id="evt-desc"
                            v-model="form.description"
                            rows="2"
                            placeholder="Optional details…"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
                        />
                    </div>
                </div>

                <!-- Row 2: Type / Status / Priority / Milestone -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    <div class="space-y-1.5">
                        <Label>Type</Label>
                        <Select v-model="form.type">
                            <SelectTrigger class="h-8 text-xs w-full"><SelectValue /></SelectTrigger>
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
                            <SelectTrigger class="h-8 text-xs w-full"><SelectValue /></SelectTrigger>
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
                            <SelectTrigger class="h-8 text-xs w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="critical">Critical</SelectItem>
                                <SelectItem value="high">High</SelectItem>
                                <SelectItem value="medium">Medium</SelectItem>
                                <SelectItem value="low">Low</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-1.5">
                        <Label>Milestone</Label>
                        <Select v-model="form.milestone_id">
                            <SelectTrigger class="h-8 text-xs w-full"><SelectValue placeholder="Backlog" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">Backlog (no milestone)</SelectItem>
                                <SelectItem v-for="m in milestones" :key="m.id" :value="m.id">{{ m.title }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Row 3: All day toggle + Start + End + Location -->
                <div class="grid grid-cols-1 sm:grid-cols-[auto_1fr_1fr_1fr] gap-4 items-end">
                    <!-- All day toggle -->
                    <div class="space-y-1.5">
                        <Label class="text-xs">All day</Label>
                        <button
                            type="button"
                            class="flex items-center gap-2 h-8 px-3 rounded-md border text-xs font-medium transition-colors"
                            :class="form.is_all_day ? 'bg-primary/10 border-primary/40 text-primary' : 'border-border text-muted-foreground hover:text-foreground hover:border-foreground/30'"
                            @click="form.is_all_day = !form.is_all_day"
                        >
                            <span
                                class="inline-flex size-3.5 items-center justify-center rounded border transition-colors shrink-0"
                                :class="form.is_all_day ? 'bg-primary border-primary' : 'border-current'"
                            >
                                <svg v-if="form.is_all_day" viewBox="0 0 10 10" class="size-2.5 text-primary-foreground" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1.5 5l2.5 2.5 4.5-4" />
                                </svg>
                            </span>
                            All day
                        </button>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="evt-start" class="text-xs">Start</Label>
                        <input
                            id="evt-start"
                            v-model="form.start_at"
                            :type="form.is_all_day ? 'date' : 'datetime-local'"
                            class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <p v-if="errors.start_at" class="text-xs text-destructive">{{ errors.start_at }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="evt-end" class="text-xs">End</Label>
                        <input
                            id="evt-end"
                            v-model="form.end_at"
                            :type="form.is_all_day ? 'date' : 'datetime-local'"
                            class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                        />
                        <p v-if="errors.end_at" class="text-xs text-destructive">{{ errors.end_at }}</p>
                    </div>
                    <div class="space-y-1.5">
                        <Label for="evt-location" class="text-xs">Location</Label>
                        <Input id="evt-location" v-model="form.location" placeholder="Optional" class="h-8 text-xs" />
                    </div>
                </div>

                <!-- Row 4: Tags + Visibility -->
                <div class="grid grid-cols-1 sm:grid-cols-[1fr_auto] gap-4 items-start">
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
                    <div class="space-y-1.5 min-w-32">
                        <Label>Visibility</Label>
                        <Select v-model="form.visibility">
                            <SelectTrigger class="h-8 text-xs w-full"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="private">Private</SelectItem>
                                <SelectItem value="shared">Shared</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </form>

            <DrawerFooter class="px-6 py-4 border-t border-border shrink-0 flex flex-row gap-2 justify-end">
                <DrawerClose as-child>
                    <Button variant="outline" type="button">Cancel</Button>
                </DrawerClose>
                <Button type="button" :disabled="processing || !form.title.trim()" @click="submit">
                    {{ isEdit() ? 'Save changes' : 'Create event' }}
                </Button>
            </DrawerFooter>
        </DrawerContent>
    </Drawer>
</template>

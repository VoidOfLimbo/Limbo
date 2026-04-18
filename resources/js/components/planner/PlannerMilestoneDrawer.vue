<script setup lang="ts">
import { reactive, watch } from 'vue'
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
import { store, update } from '@/actions/App/Http/Controllers/Planner/MilestoneController'
import type { PlannerMilestone } from '@/types/planner'

const props = defineProps<{
    open: boolean
    milestone?: PlannerMilestone | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    saved: []
}>()

const isEdit = () => !!props.milestone

const form = reactive({
    title: '',
    description: '',
    status: 'active',
    priority: 'medium',
    deadline_type: 'soft',
    duration_source: 'derived',
    progress_source: 'derived',
    start_at: '',
    end_at: '',
    color: '',
    visibility: 'private',
})

const errors = reactive<Record<string, string>>({})
let processing = false

watch(
    () => props.milestone,
    (m) => {
        if (m) {
            form.title = m.title
            form.description = m.description ?? ''
            form.status = m.status
            form.priority = m.priority
            form.deadline_type = m.deadline_type
            form.duration_source = m.duration_source
            form.progress_source = m.progress_source
            form.start_at = m.start_at ? m.start_at.slice(0, 10) : ''
            form.end_at = m.end_at ? m.end_at.slice(0, 10) : ''
            form.color = m.color ?? ''
            form.visibility = m.visibility
        } else {
            form.title = ''
            form.description = ''
            form.status = 'active'
            form.priority = 'medium'
            form.deadline_type = 'soft'
            form.duration_source = 'derived'
            form.progress_source = 'derived'
            form.start_at = ''
            form.end_at = ''
            form.color = ''
            form.visibility = 'private'
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
        status: form.status,
        priority: form.priority,
        deadline_type: form.deadline_type,
        duration_source: form.duration_source,
        progress_source: form.progress_source,
        start_at: form.start_at || undefined,
        end_at: form.end_at || undefined,
        color: form.color || undefined,
        visibility: form.visibility,
    }

    const definition = isEdit() ? update(props.milestone!.id) : store()

    router.visit(definition.url, {
        method: definition.method,
        data: payload,
        preserveScroll: true,
        only: ['milestones', 'events'],
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
                <SheetTitle>{{ isEdit() ? 'Edit milestone' : 'New milestone' }}</SheetTitle>
                <SheetDescription class="sr-only">{{ isEdit() ? 'Edit milestone details' : 'Create a new milestone' }}</SheetDescription>
            </SheetHeader>

            <form class="flex flex-col gap-5 px-6 py-5 flex-1 overflow-y-auto" @submit.prevent="submit">
                <!-- Title -->
                <div class="space-y-1.5">
                    <Label for="ms-title">Title <span class="text-destructive">*</span></Label>
                    <Input id="ms-title" v-model="form.title" placeholder="e.g. Launch my freelance business" autofocus />
                    <p v-if="errors.title" class="text-xs text-destructive">{{ errors.title }}</p>
                </div>

                <!-- Description -->
                <div class="space-y-1.5">
                    <Label for="ms-desc">Description</Label>
                    <textarea
                        id="ms-desc"
                        v-model="form.description"
                        rows="3"
                        placeholder="Optional details…"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring resize-none"
                    />
                </div>

                <!-- Status / Priority -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="completed">Completed</SelectItem>
                                <SelectItem value="paused">Paused</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
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

                <!-- Deadline type -->
                <div class="space-y-1.5">
                    <Label>Deadline type</Label>
                    <Select v-model="form.deadline_type" :disabled="isEdit() && milestone?.deadline_type === 'hard' && !!milestone?.end_at">
                        <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="soft">Soft — extends with child events</SelectItem>
                            <SelectItem value="hard">Hard — locked deadline</SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-[11px] text-muted-foreground">
                        <template v-if="form.deadline_type === 'hard'">Hard deadlines cannot be extended. The end date is locked once set.</template>
                        <template v-else>Soft deadlines automatically extend when child events grow beyond them.</template>
                    </p>
                </div>

                <!-- Duration source -->
                <div class="space-y-1.5">
                    <Label>Date range</Label>
                    <Select v-model="form.duration_source">
                        <SelectTrigger class="h-8 text-xs"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="derived">Derived from events</SelectItem>
                            <SelectItem value="manual">Manual</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Dates (always shown for hard deadline; shown for manual; informational for derived) -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1">
                        <Label for="ms-start" class="text-xs">Start date</Label>
                        <input
                            id="ms-start"
                            v-model="form.start_at"
                            type="date"
                            :disabled="form.duration_source === 'derived'"
                            class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50"
                        />
                        <p v-if="errors.start_at" class="text-xs text-destructive">{{ errors.start_at }}</p>
                    </div>
                    <div class="space-y-1">
                        <Label for="ms-end" class="text-xs">
                            End date
                            <span v-if="form.deadline_type === 'hard'" class="text-destructive">*</span>
                        </Label>
                        <input
                            id="ms-end"
                            v-model="form.end_at"
                            type="date"
                            :disabled="isEdit() && milestone?.deadline_type === 'hard' && !!milestone?.end_at"
                            class="w-full h-8 rounded-md border border-input bg-background px-2 text-xs focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:opacity-50"
                        />
                        <p v-if="errors.end_at" class="text-xs text-destructive">{{ errors.end_at }}</p>
                    </div>
                </div>

                <!-- Color -->
                <div class="space-y-1.5">
                    <Label for="ms-color">Color</Label>
                    <div class="flex items-center gap-2">
                        <input
                            id="ms-color"
                            :value="form.color || '#3b82f6'"
                            type="color"
                            class="h-8 w-12 rounded border border-input cursor-pointer bg-background"
                            @input="form.color = ($event.target as HTMLInputElement).value"
                        />
                        <Input
                            v-model="form.color"
                            placeholder="#3b82f6"
                            class="h-8 text-xs font-mono"
                            maxlength="7"
                        />
                        <Button
                            v-if="form.color"
                            variant="ghost"
                            size="sm"
                            class="h-8 px-2 text-xs text-muted-foreground"
                            type="button"
                            @click="form.color = ''"
                        >
                            Clear
                        </Button>
                    </div>
                    <p v-if="errors.color" class="text-xs text-destructive">{{ errors.color }}</p>
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
                    {{ isEdit() ? 'Save changes' : 'Create milestone' }}
                </Button>
            </SheetFooter>
        </SheetContent>
    </Sheet>
</template>

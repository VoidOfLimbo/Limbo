<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import { X, Plus, Trash2, GripVertical, ChevronDown, ChevronRight } from 'lucide-vue-next'
import {
    Drawer,
    DrawerContent,
    DrawerDescription,
    DrawerHeader,
    DrawerTitle,
    DrawerClose,
} from '@/components/ui/drawer'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import {
    store,
    update,
    destroy,
    storeOption,
    destroyOption,
} from '@/actions/App/Http/Controllers/Planner/PlannerFieldController'
import type { PlannerField, PlannerFieldType } from '@/types/planner'

const props = defineProps<{
    open: boolean
    fields: PlannerField[]
    milestoneId: string | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
}>()

// ── Field type options ──────────────────────────────────────────────────────
const FIELD_TYPES: { value: PlannerFieldType; label: string }[] = [
    { value: 'text', label: 'Text' },
    { value: 'number', label: 'Number' },
    { value: 'date', label: 'Date' },
    { value: 'checkbox', label: 'Checkbox' },
    { value: 'url', label: 'URL' },
    { value: 'single_select', label: 'Single Select' },
    { value: 'multi_select', label: 'Multi Select' },
]

// ── Create form ─────────────────────────────────────────────────────────────
const creating = ref(false)
const createName = ref('')
const createType = ref<PlannerFieldType>('text')
const createErrors = ref<Record<string, string>>({})

function submitCreate() {
    createErrors.value = {}
    router.post(
        store().url,
        {
            name: createName.value,
            type: createType.value,
            milestone_id: props.milestoneId ?? undefined,
        },
        {
            preserveScroll: true,
            only: ['fields'],
            onError: (e) => { createErrors.value = e },
            onSuccess: () => {
                creating.value = false
                createName.value = ''
                createType.value = 'text'
            },
        },
    )
}

// ── Inline edit name ────────────────────────────────────────────────────────
const editingId = ref<string | null>(null)
const editingName = ref('')

function startEdit(field: PlannerField) {
    editingId.value = field.id
    editingName.value = field.name
}

function submitEdit(field: PlannerField) {
    if (!editingName.value.trim()) return
    router.put(
        update(field.id).url,
        { name: editingName.value },
        {
            preserveScroll: true,
            only: ['fields'],
            onSuccess: () => { editingId.value = null },
        },
    )
}

function cancelEdit() {
    editingId.value = null
}

// ── Delete field ─────────────────────────────────────────────────────────────
function deleteField(field: PlannerField) {
    router.delete(
        destroy(field.id).url,
        {
            preserveScroll: true,
            only: ['fields'],
        },
    )
}

// ── Select options ──────────────────────────────────────────────────────────
const expandedId = ref<string | null>(null)
const newOptionLabels = ref<Record<string, string>>({})

function toggleExpand(field: PlannerField) {
    expandedId.value = expandedId.value === field.id ? null : field.id
}

function addOption(field: PlannerField) {
    const label = (newOptionLabels.value[field.id] ?? '').trim()
    if (!label) return
    router.post(
        storeOption(field.id).url,
        { label },
        {
            preserveScroll: true,
            only: ['fields'],
            onSuccess: () => { newOptionLabels.value[field.id] = '' },
        },
    )
}

function removeOption(field: PlannerField, optionId: string) {
    router.delete(
        destroyOption({ plannerField: field.id, optionId }).url,
        {
            preserveScroll: true,
            only: ['fields'],
        },
    )
}

// ── Computed: custom vs system ──────────────────────────────────────────────
const systemFields = computed(() => props.fields.filter((f) => f.is_system))
const customFields = computed(() => props.fields.filter((f) => !f.is_system))
</script>

<template>
    <Drawer :open="open" direction="bottom" @update:open="emit('update:open', $event)">
        <DrawerContent class="h-[95vh] flex flex-col gap-0 p-0 overflow-hidden">
            <!-- Header -->
            <DrawerHeader class="flex-row items-center justify-between px-6 py-4 border-b border-border shrink-0">
                <DrawerTitle class="text-base">Manage fields</DrawerTitle>
                <DrawerDescription class="sr-only">Create, rename, or delete custom fields for this milestone</DrawerDescription>
                <DrawerClose as-child>
                    <Button variant="ghost" size="icon" class="size-8 shrink-0 text-muted-foreground hover:text-foreground">
                        <X class="size-4" />
                    </Button>
                </DrawerClose>
            </DrawerHeader>

            <!-- Scrollable body -->
            <div class="flex-1 min-h-0 overflow-y-auto px-6 py-5 space-y-6">

                <!-- System fields (read-only) -->
                <section>
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">System fields</h3>
                    <ul class="space-y-1">
                        <li
                            v-for="field in systemFields"
                            :key="field.id"
                            class="flex items-center gap-3 px-3 py-2 rounded-md bg-muted/40 text-sm text-muted-foreground"
                        >
                            <GripVertical class="size-3.5 opacity-30 shrink-0" />
                            <span class="flex-1">{{ field.name }}</span>
                            <span class="text-xs px-1.5 py-0.5 rounded bg-muted text-muted-foreground capitalize">{{ field.type.replace('_', ' ') }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Custom fields -->
                <section>
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider mb-3">Custom fields</h3>

                    <ul v-if="customFields.length" class="space-y-1 mb-3">
                        <li
                            v-for="field in customFields"
                            :key="field.id"
                            class="rounded-md border border-border"
                        >
                            <!-- Field row -->
                            <div class="flex items-center gap-2 px-3 py-2">
                                <GripVertical class="size-3.5 text-muted-foreground shrink-0" />

                                <!-- Inline name edit -->
                                <template v-if="editingId === field.id">
                                    <input
                                        v-model="editingName"
                                        class="flex-1 bg-transparent text-sm outline-none border-b border-primary focus:outline-none select-text"
                                        @keyup.enter="submitEdit(field)"
                                        @keyup.escape="cancelEdit"
                                        @blur="submitEdit(field)"
                                        autofocus
                                    />
                                </template>
                                <button
                                    v-else
                                    class="flex-1 text-sm text-left hover:text-primary transition-colors"
                                    @click="startEdit(field)"
                                >
                                    {{ field.name }}
                                </button>

                                <span class="text-xs text-muted-foreground shrink-0 capitalize">{{ field.type.replace('_', ' ') }}</span>

                                <!-- Expand options toggle (select types only) -->
                                <button
                                    v-if="field.type === 'single_select' || field.type === 'multi_select'"
                                    class="text-muted-foreground hover:text-foreground transition-colors"
                                    @click="toggleExpand(field)"
                                >
                                    <ChevronDown
                                        class="size-3.5 transition-transform duration-150"
                                        :class="expandedId === field.id ? 'rotate-180' : ''"
                                    />
                                </button>

                                <!-- Delete -->
                                <button
                                    class="text-muted-foreground hover:text-destructive transition-colors"
                                    @click="deleteField(field)"
                                >
                                    <Trash2 class="size-3.5" />
                                </button>
                            </div>

                            <!-- Options panel -->
                            <div v-if="expandedId === field.id && (field.type === 'single_select' || field.type === 'multi_select')" class="border-t border-border px-3 py-2 space-y-1">
                                <div
                                    v-for="opt in field.options ?? []"
                                    :key="opt.id"
                                    class="flex items-center gap-2 py-1"
                                >
                                    <span
                                        v-if="opt.color"
                                        class="size-2 rounded-full shrink-0"
                                        :style="{ backgroundColor: opt.color }"
                                    />
                                    <span class="flex-1 text-sm">{{ opt.label }}</span>
                                    <button
                                        class="text-muted-foreground hover:text-destructive transition-colors"
                                        @click="removeOption(field, opt.id)"
                                    >
                                        <X class="size-3" />
                                    </button>
                                </div>

                                <!-- Add option input -->
                                <div class="flex items-center gap-2 pt-1">
                                    <input
                                        v-model="newOptionLabels[field.id]"
                                        placeholder="Add option…"
                                        class="flex-1 bg-transparent text-sm outline-none border-b border-input focus:border-primary placeholder:text-muted-foreground select-text"
                                        @keyup.enter="addOption(field)"
                                    />
                                    <button
                                        class="text-muted-foreground hover:text-primary transition-colors"
                                        @click="addOption(field)"
                                    >
                                        <Plus class="size-3.5" />
                                    </button>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <p v-else class="text-sm text-muted-foreground mb-3">No custom fields yet.</p>

                    <!-- Create new field -->
                    <div v-if="creating" class="rounded-md border border-border p-3 space-y-3">
                        <div class="space-y-1.5">
                            <Label for="new-field-name">Field name <span class="text-destructive">*</span></Label>
                            <Input
                                id="new-field-name"
                                v-model="createName"
                                placeholder="e.g. Story Points"
                                autofocus
                                @keyup.enter="submitCreate"
                                @keyup.escape="creating = false"
                            />
                            <p v-if="createErrors.name" class="text-xs text-destructive">{{ createErrors.name }}</p>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Field type</Label>
                            <Select v-model="createType">
                                <SelectTrigger class="h-8 text-xs w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in FIELD_TYPES" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex items-center gap-2">
                            <Button size="sm" @click="submitCreate">Add field</Button>
                            <Button size="sm" variant="ghost" @click="creating = false; createName = ''; createType = 'text'">Cancel</Button>
                        </div>
                    </div>

                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        class="gap-1.5 mt-1"
                        @click="creating = true"
                    >
                        <Plus class="size-3.5" />
                        New field
                    </Button>
                </section>
            </div>
        </DrawerContent>
    </Drawer>
</template>

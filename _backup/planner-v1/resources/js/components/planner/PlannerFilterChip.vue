<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'

/**
 * Shared chip used across filter rows (status, priority, tags).
 * Supports two color modes:
 *   - `tone`: a preset Tailwind color name; produces consistent active/inactive/hover styles
 *   - `color`: a raw hex color (used by tags); produces tinted active state and a colored hover ring
 */

type Tone =
    | 'zinc' | 'slate' | 'blue' | 'amber' | 'emerald' | 'red'
    | 'rose' | 'orange' | 'sky' | 'violet' | 'green' | 'gray'

const props = defineProps<{
    label: string
    active: boolean
    tone?: Tone
    color?: string | null
    class?: string
}>()

const toneClasses: Record<Tone, { active: string; inactive: string }> = {
    zinc: {
        active: 'bg-zinc-500/15 border-zinc-500/60 text-zinc-300',
        inactive: 'border-border text-muted-foreground hover:border-zinc-500/50 hover:text-zinc-400',
    },
    slate: {
        active: 'bg-slate-500/15 border-slate-500/60 text-slate-300',
        inactive: 'border-border text-muted-foreground hover:border-slate-500/50 hover:text-slate-400',
    },
    blue: {
        active: 'bg-blue-500/15 border-blue-500/60 text-blue-300',
        inactive: 'border-border text-muted-foreground hover:border-blue-500/50 hover:text-blue-400',
    },
    amber: {
        active: 'bg-amber-500/15 border-amber-500/60 text-amber-300',
        inactive: 'border-border text-muted-foreground hover:border-amber-500/50 hover:text-amber-400',
    },
    emerald: {
        active: 'bg-emerald-500/15 border-emerald-500/60 text-emerald-300',
        inactive: 'border-border text-muted-foreground hover:border-emerald-500/50 hover:text-emerald-400',
    },
    red: {
        active: 'bg-red-500/15 border-red-500/60 text-red-300',
        inactive: 'border-border text-muted-foreground hover:border-red-500/50 hover:text-red-400',
    },
    rose: {
        active: 'bg-rose-500/15 border-rose-500/60 text-rose-300',
        inactive: 'border-border text-muted-foreground hover:border-rose-500/50 hover:text-rose-400',
    },
    orange: {
        active: 'bg-orange-500/15 border-orange-500/60 text-orange-300',
        inactive: 'border-border text-muted-foreground hover:border-orange-500/50 hover:text-orange-400',
    },
    sky: {
        active: 'bg-sky-500/15 border-sky-500/60 text-sky-300',
        inactive: 'border-border text-muted-foreground hover:border-sky-500/50 hover:text-sky-400',
    },
    violet: {
        active: 'bg-violet-500/15 border-violet-500/60 text-violet-300',
        inactive: 'border-border text-muted-foreground hover:border-violet-500/50 hover:text-violet-400',
    },
    green: {
        active: 'bg-green-500/15 border-green-500/60 text-green-300',
        inactive: 'border-border text-muted-foreground hover:border-green-500/50 hover:text-green-400',
    },
    gray: {
        active: 'bg-gray-500/15 border-gray-500/60 text-gray-300',
        inactive: 'border-border text-muted-foreground hover:border-gray-500/50 hover:text-gray-400',
    },
}

function hexToRgba(hex: string, alpha: number): string | undefined {
    const m = hex.replace('#', '').match(/^([0-9a-f]{6}|[0-9a-f]{3})$/i)
    if (!m) return undefined
    let h = m[1]
    if (h.length === 3) h = h.split('').map((c) => c + c).join('')
    const r = parseInt(h.slice(0, 2), 16)
    const g = parseInt(h.slice(2, 4), 16)
    const b = parseInt(h.slice(4, 6), 16)
    return `rgba(${r}, ${g}, ${b}, ${alpha})`
}

const toneClass = computed(() => {
    if (!props.tone) return ''
    const t = toneClasses[props.tone]
    return props.active ? t.active : t.inactive
})

const colorStyle = computed<Record<string, string>>(() => {
    if (!props.color) return {}
    if (props.active) {
        return {
            backgroundColor: hexToRgba(props.color, 0.15) ?? '',
            borderColor: hexToRgba(props.color, 0.6) ?? '',
            color: props.color,
        }
    }
    return {}
})

const colorVar = computed<Record<string, string>>(() => {
    if (!props.color || props.active) return {}
    // Used by hover styles via CSS var
    return {
        '--chip-color': props.color,
        '--chip-color-soft': hexToRgba(props.color, 0.5) ?? props.color,
    }
})
</script>

<template>
    <button
        type="button"
        :class="
            cn(
                'inline-flex items-center px-2.5 py-0.5 rounded-full border text-[11px] font-medium transition-colors cursor-pointer',
                tone ? toneClass : '',
                !tone && !active ? 'border-border text-muted-foreground hover:[border-color:var(--chip-color-soft)] hover:[color:var(--chip-color)]' : '',
                props.class,
            )
        "
        :style="{ ...colorStyle, ...colorVar }"
    >
        {{ label }}
    </button>
</template>

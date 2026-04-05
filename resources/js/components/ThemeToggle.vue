<script setup lang="ts">
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { useAppearance } from '@/composables/useAppearance';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    { value: 'light', Icon: Sun, label: 'Light' },
    { value: 'dark', Icon: Moon, label: 'Dark' },
    { value: 'system', Icon: Monitor, label: 'System' },
] as const;
</script>

<template>
    <TooltipProvider :delay-duration="300">
        <div class="inline-flex gap-0.5 rounded-lg bg-muted p-1">
            <Tooltip v-for="{ value, Icon, label } in tabs" :key="value">
                <TooltipTrigger as-child>
                    <button
                        @click="updateAppearance(value)"
                        :aria-label="`Switch to ${label} mode`"
                        :class="[
                            'flex size-7 cursor-pointer items-center justify-center rounded-md transition-all',
                            appearance === value
                                ? 'bg-background text-foreground shadow-xs dark:bg-neutral-700'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                    >
                        <component :is="Icon" class="size-3.5" />
                    </button>
                </TooltipTrigger>
                <TooltipContent side="bottom">{{ label }}</TooltipContent>
            </Tooltip>
        </div>
    </TooltipProvider>
</template>

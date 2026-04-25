<script setup lang="ts">
import type { Component } from 'vue'
import { CalendarX2 } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    icon?: Component
    title?: string
    description?: string
    actionLabel?: string
}>()

const emit = defineEmits<{
    action: []
}>()
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-3 py-20 text-center px-4">
        <component
            :is="icon ?? CalendarX2"
            class="size-10 text-muted-foreground/40"
        />
        <div class="space-y-1">
            <p class="text-sm font-medium text-muted-foreground">
                {{ title ?? 'Nothing here yet' }}
            </p>
            <p v-if="description" class="text-xs text-muted-foreground/60">
                {{ description }}
            </p>
        </div>
        <Button
            v-if="actionLabel"
            variant="outline"
            size="sm"
            @click="emit('action')"
        >
            {{ actionLabel }}
        </Button>
    </div>
</template>

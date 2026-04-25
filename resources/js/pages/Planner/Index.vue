<script setup lang="ts">
import { Head } from '@inertiajs/vue3'
import { planner } from '@/routes'

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Planner',
                href: planner(),
            },
        ],
    },
})
</script>

<template>
    <Head title="Planner" />

    <div class="flex min-h-full flex-1 items-center justify-center p-6">
        <p class="text-muted-foreground text-sm">This is a planner view.</p>
    </div>
</template>

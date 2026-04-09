<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon, TicketIcon } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
</script>

<template>
    <Head title="Invite Code — VoidOfLimbo" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-border/40 bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex h-14 max-w-2xl items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <Link :href="home()">
                        <Button variant="ghost" size="sm" class="gap-2 text-muted-foreground">
                            <ArrowLeftIcon class="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div class="h-4 w-px bg-border" />
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center rounded-sm dark:bg-white dark:p-0.5">
                            <AppLogoIcon className="size-5" />
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">VoidOfLimbo</span>
                    </div>
                </div>
                <ThemeToggle />
            </div>
        </header>

        <!-- Content -->
        <main class="flex min-h-[calc(100vh-3.5rem)] flex-col items-center justify-center px-6 py-20 text-center">
            <div class="flex size-16 items-center justify-center rounded-2xl border border-border/60 bg-card">
                <TicketIcon class="size-7 text-muted-foreground" />
            </div>

            <Badge variant="secondary" class="mt-6">Coming soon</Badge>

            <h1 class="mt-4 text-2xl font-bold tracking-tight lg:text-3xl">Invite codes</h1>

            <p class="mt-3 max-w-sm text-sm leading-relaxed text-muted-foreground">
                Invite code redemption is not available yet. Early access invites will be distributed by server owners once the feature launches.
            </p>

            <Link :href="home()" class="mt-8">
                <Button variant="outline">Return to home</Button>
            </Link>
        </main>
    </div>
</template>

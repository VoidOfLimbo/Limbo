<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BlocksIcon,
    CalendarIcon,
    ChevronRightIcon,
    LayoutDashboardIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TicketIcon,
    UsersIcon,
    WalletIcon,
} from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        icon: BlocksIcon,
        title: 'Page Builder',
        description:
            'Compose rich pages using a flexible block-based editor — text, media, embeds, forms, and more. Each page carries its own visibility level.',
        badge: 'Servers',
    },
    {
        icon: UsersIcon,
        title: 'Community Servers',
        description:
            'Create a branded community space with your own pages, members, and subscription tiers. Invite users and set visibility rules per page.',
        badge: 'Community',
    },
    {
        icon: WalletIcon,
        title: 'Expense Planner',
        description:
            'Track and plan your finances privately. Your productivity data is yours — never exposed to the community unless you choose to share it.',
        badge: 'Productivity',
    },
    {
        icon: CalendarIcon,
        title: 'Life Planner',
        description:
            'Organise your goals, habits, and schedule in one place. Built-in smart utility modules available to all registered users.',
        badge: 'Productivity',
    },
    {
        icon: ShieldCheckIcon,
        title: 'Granular Access Control',
        description:
            'A fully ordered visibility stack — from public to owner-only. Plus demo access, invite links with OTP, and private per-user grants.',
        badge: 'Access',
    },
    {
        icon: SparklesIcon,
        title: 'Loyalist Perks',
        description:
            'Animated profiles, special effects, particle overlays, and insider access. Recurring subscribers unlock the full creative experience.',
        badge: 'Premium',
    },
];
</script>

<template>
    <Head title="Welcome to Limbo" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-border/40 bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex h-14 items-center justify-between px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-7 items-center justify-center rounded-md bg-primary">
                        <AppLogoIcon className="size-4 fill-current text-primary-foreground" />
                    </div>
                    <span class="text-sm font-semibold tracking-tight">Limbo</span>
                </div>

                <nav class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button variant="outline" size="sm">Dashboard</Button>
                    </Link>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div
                    class="absolute left-1/2 top-0 h-[500px] w-[800px] -translate-x-1/2 rounded-full bg-primary/5 blur-3xl"
                />
            </div>

            <div class="mx-auto max-w-6xl px-6 py-24 text-center lg:py-36">
                <Badge variant="outline" class="mb-6 gap-1.5 px-3 py-1">
                    <SparklesIcon class="size-3" />
                    Now in early access
                </Badge>

                <h1 class="mx-auto max-w-3xl text-4xl font-bold tracking-tight lg:text-6xl">
                    Your community.<br />
                    <span class="text-primary">Your space.</span>
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-base text-muted-foreground lg:text-lg">
                    Limbo is a community platform with a flexible page builder and personal productivity tools.
                    Build your space, invite your people, and own your experience.
                </p>

                <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <Link v-if="canRegister" :href="register()">
                        <Button size="lg" class="gap-2">
                            Create Free Account
                            <ChevronRightIcon class="size-4" />
                        </Button>
                    </Link>
                    <Link :href="login()">
                        <Button variant="outline" size="lg">Log in</Button>
                    </Link>
                    <Link href="/invite">
                        <Button variant="ghost" size="lg" class="gap-2">
                            <TicketIcon class="size-4" />
                            I have an invite code
                        </Button>
                    </Link>
                </div>

                <p class="mt-4 text-xs text-muted-foreground">Creating an account is free. Some features require a subscription.</p>
            </div>
        </section>

        <Separator />

        <!-- Features -->
        <section class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-12 text-center">
                <Badge variant="secondary" class="mb-3 gap-1.5">
                    <LayoutDashboardIcon class="size-3" />
                    Features
                </Badge>
                <h2 class="text-2xl font-bold tracking-tight lg:text-3xl">
                    Everything you need to build your community
                </h2>
                <p class="mt-3 text-sm text-muted-foreground">From page builder to productivity tools — all in one place.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="group rounded-xl border border-border/60 bg-card p-6 transition-colors hover:border-border hover:bg-card/80"
                >
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex size-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <component :is="feature.icon" class="size-4" />
                        </div>
                        <Badge variant="outline" class="text-xs">{{ feature.badge }}</Badge>
                    </div>
                    <h3 class="mb-1.5 font-semibold">{{ feature.title }}</h3>
                    <p class="text-sm leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                </div>
            </div>
        </section>

        <Separator />

        <!-- CTA -->
        <section class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="text-2xl font-bold tracking-tight lg:text-3xl">Ready to join Limbo?</h2>
            <p class="mt-3 text-sm text-muted-foreground">Create a free account and explore the platform. Premium features available via subscription.</p>
            <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <Link v-if="canRegister" :href="register()">
                    <Button size="lg" class="gap-2">
                        Create Free Account
                        <ChevronRightIcon class="size-4" />
                    </Button>
                </Link>
                <Link href="/invite">
                    <Button variant="outline" size="lg" class="gap-2">
                        <TicketIcon class="size-4" />
                        I have an invite code
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border/40">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
                <div class="flex items-center gap-2">
                    <div class="flex size-5 items-center justify-center rounded bg-primary">
                        <AppLogoIcon className="size-3 fill-current text-primary-foreground" />
                    </div>
                    <span class="text-xs font-medium text-muted-foreground">Limbo by VoidOfLimbo</span>
                </div>
                <p class="text-xs text-muted-foreground">© {{ new Date().getFullYear() }} Limbo. All rights reserved.</p>
            </div>
        </footer>
    </div>
</template>

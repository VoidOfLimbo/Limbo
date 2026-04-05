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
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { dashboard, invite, login, privacyPolicy, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const prefixes = ['By the', 'For the', 'Of the'];
const currentIndex = ref(0);
let cycleInterval: ReturnType<typeof setInterval>;

onMounted(() => {
    cycleInterval = setInterval(() => {
        currentIndex.value = (currentIndex.value + 1) % prefixes.length;
    }, 2200);
});

onBeforeUnmount(() => {
    clearInterval(cycleInterval);
});

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
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex size-8 items-center justify-center rounded-md bg-primary">
                        <AppLogoIcon className="size-4 fill-current text-primary-foreground" />
                    </div>
                    <span class="font-semibold tracking-tight">Limbo</span>
                </div>

                <nav class="flex items-center gap-2">
                    <ThemeToggle />
                    <template v-if="$page.props.auth.user">
                        <Link :href="dashboard()">
                            <Button variant="outline" size="sm">Dashboard</Button>
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()">
                            <Button variant="ghost" size="sm">Log in</Button>
                        </Link>
                        <Link v-if="canRegister" :href="register()">
                            <Button size="sm">Get started</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute left-1/2 top-0 h-[600px] w-[900px] -translate-x-1/2 rounded-full bg-primary/5 blur-3xl" />
            </div>

            <div class="mx-auto max-w-4xl px-6 py-36 text-center lg:py-48">
                <p class="mb-6 text-sm font-medium tracking-widest text-muted-foreground uppercase">How did we get here?</p>

                <h1 class="mx-auto flex flex-col items-center leading-none">
                    <span class="relative mb-2 flex h-[1.2em] items-center overflow-hidden text-4xl font-bold tracking-tight text-muted-foreground lg:text-6xl">
                        <Transition name="prefix" mode="out-in">
                            <span :key="currentIndex" class="block">{{ prefixes[currentIndex] }}</span>
                        </Transition>
                    </span>
                    <span class="text-7xl font-extrabold tracking-tight lg:text-[9rem]">Developer.</span>
                </h1>

                <p class="mx-auto mt-10 max-w-md text-base leading-relaxed text-muted-foreground lg:text-lg">
                    Limbo is a long lost Dream of a rouge developer which has become reality. Check it out!
                </p>

                <div class="mt-12 flex flex-wrap items-center justify-center gap-4">
                    <Link v-if="canRegister" :href="register()">
                        <Button size="lg" class="h-12 gap-2 px-8 text-base">
                            Create Free Account
                            <ChevronRightIcon class="size-4" />
                        </Button>
                    </Link>
                    <Link :href="invite()">
                        <Button variant="outline" size="lg" class="h-12 gap-2 px-8 text-base">
                            <TicketIcon class="size-4" />
                            I have an invite code
                        </Button>
                    </Link>
                </div>

                <p class="mt-6 text-xs text-muted-foreground">Free to create an account. Some features require a subscription.</p>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-muted/30">
            <div class="mx-auto max-w-6xl px-6 py-32">
                <div class="mb-20 text-center">
                    <Badge variant="secondary" class="mb-5 gap-1.5 px-3 py-1">
                        <LayoutDashboardIcon class="size-3" />
                        Features
                    </Badge>
                    <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">
                        What's inside?
                    </h2>
                    <p class="mx-auto mt-4 max-w-md text-muted-foreground">
                        From page builder to productivity tools — all in one place.
                    </p>                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-border/50 bg-background p-8 transition-colors hover:border-border"
                    >
                        <div class="mb-6 flex items-start justify-between">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <component :is="feature.icon" class="size-5" />
                            </div>
                            <Badge variant="outline" class="text-xs font-normal text-muted-foreground">{{ feature.badge }}</Badge>
                        </div>
                        <h3 class="mb-2 text-base font-semibold">{{ feature.title }}</h3>
                        <p class="text-sm leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section class="mx-auto max-w-2xl px-6 py-32 text-center">
            <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">Ready to join Limbo?</h2>
            <p class="mx-auto mt-4 max-w-sm text-muted-foreground">
                Create a free account and explore the platform. Premium features available via subscription.
            </p>
            <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                <Link v-if="canRegister" :href="register()">
                    <Button size="lg" class="h-12 gap-2 px-8 text-base">
                        Create Free Account
                        <ChevronRightIcon class="size-4" />
                    </Button>
                </Link>
                <Link :href="invite()">
                    <Button variant="outline" size="lg" class="h-12 gap-2 px-8 text-base">
                        <TicketIcon class="size-4" />
                        I have an invite code
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-border/40">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
                <div class="flex items-center gap-2">
                    <div class="flex size-5 items-center justify-center rounded bg-primary">
                        <AppLogoIcon className="size-3 fill-current text-primary-foreground" />
                    </div>
                    <span class="text-xs font-medium text-muted-foreground">Limbo by VoidOfLimbo</span>
                </div>
                <p class="text-xs text-muted-foreground">© {{ new Date().getFullYear() }} Limbo. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <Link :href="privacyPolicy()" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Privacy Policy</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.prefix-enter-active,
.prefix-leave-active {
    transition:
        opacity 0.35s ease,
        transform 0.35s ease;
}

.prefix-enter-from {
    opacity: 0;
    transform: translateY(14px);
}

.prefix-leave-to {
    opacity: 0;
    transform: translateY(-14px);
}
</style>

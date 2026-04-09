<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BlocksIcon,
    CalendarIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    HomeIcon,
    LayoutDashboardIcon,
    RocketIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TicketIcon,
    UsersIcon,
    WalletIcon,
} from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BounceWrapper from '@/components/BounceWrapper.vue';
import GlitchText from '@/components/GlitchText.vue';
import NeonHeader from '@/components/NeonHeader.vue';
import ScrambleText from '@/components/ScrambleText.vue';
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

const navSections = [
    { id: 'hero', label: 'Home', icon: HomeIcon },
    { id: 'features', label: 'Features', icon: LayoutDashboardIcon },
    { id: 'cta', label: 'Join', icon: RocketIcon },
];

const activeSection = ref('hero');
const hoveredSection = ref<string | null>(null);
const glitchRef = ref<InstanceType<typeof GlitchText> | null>(null);

function navZoom(index: number): number {
    if (hoveredSection.value === null) {
        return 1;
    }

    const hi = navSections.findIndex((s) => s.id === hoveredSection.value);

    if (hi === -1) {
        return 1;
    }

    if (index === hi) {
        return 1.5;
    }

    if (Math.abs(index - hi) === 1) {
        return 1.2;
    }

    return 1;
}

function scrollToSection(id: string): void {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

let sectionObserver: IntersectionObserver | null = null;

onMounted(() => {
    sectionObserver = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    activeSection.value = entry.target.id;
                }
            });
        },
        { rootMargin: '-45% 0px -45% 0px', threshold: 0 },
    );

    navSections.forEach(({ id }) => {
        const el = document.getElementById(id);

        if (el) {
            sectionObserver!.observe(el);
        }
    });
});

onBeforeUnmount(() => {
    sectionObserver?.disconnect();
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
    <Head title="Welcome to VoidOfLimbo" />

    <div class="min-h-screen bg-background text-foreground overflow-hidden">
        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-border/40 bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex h-16 items-center justify-between px-6">
                <div class="flex items-center gap-2.5">
                    <div class="flex items-center justify-center rounded-md dark:bg-white dark:p-1">
                        <AppLogoIcon className="size-8" />
                    </div>
                    <span class="font-semibold tracking-tight">VoidOfLimbo</span>
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

        <!-- Section scroll nav (fixed right edge, icon pills) -->
        <nav class="fixed right-0 top-1/2 z-40 hidden -translate-y-1/2 flex-col items-end gap-2.5 lg:flex">
            <button
                v-for="(section, index) in navSections"
                :key="section.id"
                :style="{ zoom: navZoom(index) }"
                :class="[
                    'flex cursor-pointer items-center gap-2 overflow-hidden rounded-l-full border-y border-l py-2 pl-3 pr-3 shadow-sm backdrop-blur-sm transition-[color,background-color,border-color,box-shadow,zoom] duration-300',
                    activeSection === section.id
                        ? 'border-primary/60 bg-primary/15 text-primary shadow-[-4px_0_12px_2px] shadow-primary/25'
                        : 'border-border bg-card text-foreground/60 hover:border-border/80 hover:bg-card hover:text-foreground',
                ]"
                @click="scrollToSection(section.id)"
                @mouseenter="hoveredSection = section.id"
                @mouseleave="hoveredSection = null"
            >
                <component :is="section.icon" class="size-4 shrink-0" />
                <span
                    class="min-w-0 overflow-hidden whitespace-nowrap text-xs font-medium transition-all duration-300"
                    :style="{
                        maxWidth: hoveredSection === section.id ? '6rem' : '0px',
                        opacity: hoveredSection === section.id ? '1' : '0',
                    }"
                >
                    {{ section.label }}
                </span>
            </button>
        </nav>

        <!-- Hero -->
        <section id="hero" class="relative flex min-h-screen scroll-mt-16 flex-col items-center justify-center overflow-hidden">
            <div class="flex w-full flex-col items-center px-6 py-24 text-center">
                <NeonHeader
                    text="VoidOfLimbo"
                    tag="p"
                    class="mb-8 text-6xl font-extrabold tracking-tight lg:text-8xl"
                    :spread="12"
                    :base-duration="7"
                    :tilt="[{ chars: 'L', angle: 18, top: '6px' }]"
                />

                <h1 class="mx-auto flex flex-col items-center leading-none">
                    <ScrambleText
                        :texts="['By the', 'For the', 'Of the']"
                        tag="span"
                        class="relative mb-2 flex h-[1.2em] items-center py-2 text-4xl font-bold tracking-tight lg:text-6xl"
                        @change="glitchRef?.trigger()"
                    />
                    <GlitchText
                        ref="glitchRef"
                        text="Developer"
                        tag="span"
                        class="text-6xl font-extrabold tracking-tight lg:text-8xl"
                    />
                </h1>

                <p class="mx-auto mt-14 text-base leading-relaxed text-muted-foreground lg:text-lg">
                    VoidOfLimbo is a long lost dream of a rouge developer who wanted efficiency and discipline in his life.
                </p>

                <div class="mt-14 flex flex-wrap items-center justify-center gap-4">
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

                <div class="mt-10 flex flex-col items-center gap-2">
                    <p class="text-xs italic text-muted-foreground">scroll down to see more</p>
                    <BounceWrapper direction="down" distance="7px" :duration="2">
                        <ChevronDownIcon class="size-8 text-muted-foreground" />
                    </BounceWrapper>
                </div>
            </div>
        </section>

        <!-- Features -->
        <section id="features" class="flex min-h-screen scroll-mt-16 flex-col items-center justify-center bg-muted/30">
            <div class="w-full px-6 py-24">
                <div class="mb-20 text-center">
                    <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">
                        What's inside?
                    </h2>
                    <p class="mx-auto mt-4 text-muted-foreground">
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
        <section id="cta" class="flex min-h-screen scroll-mt-16 flex-col items-center justify-center px-6 py-24 text-center">
            <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">Ready to join VoidOfLimbo?</h2>
            <p class="mx-auto mt-4 text-muted-foreground">
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
            <div class="mx-auto flex flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center rounded-sm dark:bg-white dark:p-0.5">
                        <AppLogoIcon className="size-5" />
                    </div>
                    <span class="text-xs font-medium text-muted-foreground">
                        VoidOfLimbo by
                        <a href="https://www.linkedin.com/in/bipin-paneru/" target="_blank" rel="noopener noreferrer" class="cursor-pointer font-semibold text-foreground underline decoration-primary/50 underline-offset-2 transition-all hover:decoration-primary">VoidOfLimbo</a>
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">© {{ new Date().getFullYear() }} VoidOfLimbo. All rights reserved.</p>
                <div class="flex items-center gap-4">
                    <Link :href="privacyPolicy()" class="text-xs text-muted-foreground transition-colors hover:text-foreground">Privacy Policy</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

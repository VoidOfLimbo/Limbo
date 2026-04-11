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
import DynamicFloatingMenu from '@/components/DynamicFloatingMenu.vue';
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
const glitchRef = ref<InstanceType<typeof GlitchText> | null>(null);

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

    <div class="relative min-h-screen bg-background text-foreground">
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
                            <Button size="sm">Join Now</Button>
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- DynamicFloatingMenu -->
        <DynamicFloatingMenu :items="navSections" :active-item="activeSection" position="right" alignment="center"
            :collapsible="true" @select="scrollToSection" />

        <!-- Hero -->
        <section id="hero"
            class="relative flex min-h-screen scroll-mt-16 flex-col items-center justify-center overflow-hidden">
            <div class="flex w-full flex-col items-center px-6 py-24 text-center">
                <NeonHeader text="VoidOfLimbo" tag="p" class="mb-8 text-6xl font-extrabold tracking-tight lg:text-8xl"
                    :spread="12" :base-duration="7" :tilt="[{ chars: 'L', angle: 18, top: '6px' }]" />

                <ScrambleText :texts="['By the', 'For the', 'Of the']" tag="span"
                    class="relative mb-2 flex h-[1.2em] items-center py-2 text-4xl font-bold tracking-tight lg:text-6xl"
                    @change="glitchRef?.trigger()" />

                <GlitchText ref="glitchRef" text="Developer" tag="span"
                    class="text-6xl font-extrabold tracking-tight lg:text-8xl" />

                <p class="mx-auto mt-14 text-base leading-relaxed text-muted-foreground lg:text-lg">
                    Bespoke page, Interesting features, Productivity tools, Integrated community, Nihil
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
        <section id="features" class="flex min-h-screen scroll-mt-16 flex-col items-center justify-center relative">
            <div class="w-full px-6 py-24">
                <div class="mb-20 text-center">
                    <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">
                        What's inside?
                    </h2>
                    <p class="mx-auto mt-4 text-muted-foreground">
                        From page builder to productivity tools — all in one place.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="feature in features" :key="feature.title"
                        class="group rounded-2xl border border-border/60 bg-background/70 p-8 backdrop-blur-sm transition-colors hover:border-primary/40 hover:bg-background/90">
                        <div class="mb-6 flex items-start justify-between">
                            <div class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <component :is="feature.icon" class="size-5" />
                            </div>
                            <Badge variant="outline" class="text-xs font-normal text-muted-foreground">{{ feature.badge
                                }}
                            </Badge>
                        </div>
                        <h3 class="mb-2 text-base font-semibold">{{ feature.title }}</h3>
                        <p class="text-sm leading-relaxed text-muted-foreground">{{ feature.description }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA -->
        <section id="cta"
            class="flex min-h-screen scroll-mt-16 flex-col items-center justify-center px-6 py-24 text-center">
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
        <footer class="border-t border-border/40 bg-background/70 backdrop-blur-sm">
            <div class="mx-auto flex flex-col items-center justify-between gap-4 px-6 py-8 sm:flex-row">
                <div class="flex items-center gap-2">
                    <div class="flex items-center justify-center rounded-sm dark:bg-white dark:p-0.5">
                        <AppLogoIcon className="size-5" />
                    </div>
                    <span class="text-xs font-medium text-muted-foreground">
                        VoidOfLimbo by
                        <a href="https://www.linkedin.com/in/bipin-paneru/" target="_blank" rel="noopener noreferrer"
                            class="cursor-pointer font-semibold text-foreground underline decoration-primary/50 underline-offset-2 transition-all hover:decoration-primary">VoidOfLimbo</a>
                    </span>
                </div>
                <p class="text-xs text-muted-foreground">© {{ new Date().getFullYear() }} VoidOfLimbo. All rights
                    reserved.</p>
                <div class="flex items-center gap-4">
                    <Link :href="privacyPolicy()"
                        class="text-xs text-muted-foreground transition-colors hover:text-foreground">
                        Privacy Policy</Link>
                </div>
            </div>
        </footer>
    </div>
</template>

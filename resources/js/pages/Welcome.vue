<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    BlocksIcon,
    CalendarIcon,
    ChevronDownIcon,
    ChevronRightIcon,
    LayoutDashboardIcon,
    ShieldCheckIcon,
    SparklesIcon,
    TicketIcon,
    UsersIcon,
    WalletIcon,
} from 'lucide-vue-next';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTextScramble } from '@/composables/useTextScramble';
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
const { scrambled: scrambledPrefix, scramble: scramblePrefix } = useTextScramble(prefixes[0]);
const glitchVariant = ref(0);
let cycleInterval: ReturnType<typeof setInterval>;

function triggerDevGlitch(): void {
    glitchVariant.value = 0;
    requestAnimationFrame(() => {
        glitchVariant.value = Math.floor(Math.random() * 10) + 1;
        setTimeout(() => {
            glitchVariant.value = 0;
        }, 800);
    });
}

watch(currentIndex, (newVal) => {
    scramblePrefix(prefixes[newVal]);
    triggerDevGlitch();
});

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
            <div class="mx-auto flex h-16 items-center justify-between px-6">
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

            <div class="px-6 py-36 text-center lg:py-48">
                <p class="neon-header mb-6 text-6xl font-extrabold tracking-tight lg:text-8xl">How did we get here?</p>

                <h1 class="mx-auto flex flex-col items-center leading-none">
                    <span class="tech-prefix relative mb-2 flex h-[1.2em] items-center py-2 text-4xl font-bold tracking-tight lg:text-6xl">
                        <span class="block">{{ scrambledPrefix }}</span>
                    </span>
                    <span
                        :class="['glitch-text text-6xl font-extrabold tracking-tight lg:text-8xl', glitchVariant > 0 ? `glitch-fire-${glitchVariant}` : '']"
                        data-text="Developer"
                    >Developer</span>
                </h1>

                <p class="mx-auto mt-10 text-base leading-relaxed text-muted-foreground lg:text-lg">
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

                <div class="mt-6 flex flex-col items-center gap-2">
                    <p class="text-xs text-muted-foreground">Looking for someone specific? scroll down to see more</p>
                    <ChevronDownIcon class="bounce-arrow size-4 text-muted-foreground" />
                </div>
            </div>
        </section>

        <!-- Features -->
        <section class="bg-muted/30">
            <div class="px-6 py-32">
                <div class="mb-20 text-center">
                    <Badge variant="secondary" class="mb-5 gap-1.5 px-3 py-1">
                        <LayoutDashboardIcon class="size-3" />
                        Features
                    </Badge>
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
        <section class="px-6 py-32 text-center">
            <h2 class="text-3xl font-bold tracking-tight lg:text-4xl">Ready to join Limbo?</h2>
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
                    <div class="flex size-5 items-center justify-center rounded bg-primary">
                        <AppLogoIcon className="size-3 fill-current text-primary-foreground" />
                    </div>
                    <span class="text-xs font-medium text-muted-foreground">
                        Limbo by
                        <a href="https://www.linkedin.com/in/bipin-paneru/" target="_blank" rel="noopener noreferrer" class="transition-colors hover:text-foreground">VoidOfLimbo</a>
                    </span>
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
/* Glitch effect on Developer — triggered on prefix change */
.glitch-text {
    position: relative;
}

.glitch-fire-1  { animation: glitch-1  0.70s forwards; }
.glitch-fire-2  { animation: glitch-2  0.65s forwards; }
.glitch-fire-3  { animation: glitch-3  0.60s forwards; }
.glitch-fire-4  { animation: glitch-4  0.75s forwards; }
.glitch-fire-5  { animation: glitch-5  0.50s forwards; }
.glitch-fire-6  { animation: glitch-6  0.80s forwards; }
.glitch-fire-7  { animation: glitch-7  0.60s forwards; }
.glitch-fire-8  { animation: glitch-8  0.70s forwards; }
.glitch-fire-9  { animation: glitch-9  0.65s forwards; }
.glitch-fire-10 { animation: glitch-10 0.75s forwards; }

/* 1: Classic RGB split + skew */
@keyframes glitch-1 {
    0%, 100% { text-shadow: none; transform: none; filter: none; }
    15% { text-shadow: -4px 0 #f0f, 4px 0 #0ff; transform: translateX(-3px) skewX(-3deg); }
    30% { text-shadow: 4px 0 #f0f, -4px 0 #0ff; transform: translateX(3px) skewX(3deg); }
    50% { text-shadow: -2px 0 #f0f, 2px 0 #0ff; transform: translateX(-1px); filter: blur(0.5px); }
    70% { text-shadow: none; transform: none; filter: none; }
}

/* 2: Rapid micro shake */
@keyframes glitch-2 {
    0%, 100% { transform: none; }
    10% { transform: translateX(-7px); }
    20% { transform: translateX(7px); }
    30% { transform: translateX(-5px); }
    40% { transform: translateX(5px); }
    50% { transform: translateX(-3px); }
    60% { transform: translateX(3px); }
    75% { transform: translateX(-1px); }
    85% { transform: none; }
}

/* 3: Horizontal tear + clip slice */
@keyframes glitch-3 {
    0%, 100% { transform: none; text-shadow: none; clip-path: none; }
    20% { transform: translateX(10px); text-shadow: -10px 0 #0ff; }
    35% { transform: translateX(-8px); text-shadow: 8px 0 #f0f; clip-path: polygon(0 0, 100% 0, 100% 45%, 0 45%); }
    50% { transform: translateX(5px); clip-path: polygon(0 55%, 100% 55%, 100% 100%, 0 100%); text-shadow: -5px 0 #0ff; }
    65% { clip-path: none; transform: none; text-shadow: none; }
}

/* 4: Heavy blur burst then snap */
@keyframes glitch-4 {
    0%, 100% { filter: none; text-shadow: none; }
    20% { filter: blur(3px) brightness(1.8); }
    40% { filter: blur(0) brightness(2) contrast(1.5); text-shadow: 0 0 25px #0ff, 0 0 50px #0ff; }
    65% { filter: blur(1.5px); text-shadow: none; }
    80% { filter: none; }
}

/* 5: Color invert flash */
@keyframes glitch-5 {
    0%, 100% { filter: none; }
    15% { filter: invert(1); }
    22% { filter: none; }
    40% { filter: invert(1) hue-rotate(90deg); }
    47% { filter: none; }
    60% { filter: invert(1) saturate(2); }
    67% { filter: none; }
}

/* 6: Neon pulse burst */
@keyframes glitch-6 {
    0%, 100% { text-shadow: none; transform: none; }
    15% { text-shadow: 0 0 30px #0ff, 0 0 60px #0ff, 0 0 100px #0ff; transform: scale(1.03); }
    35% { text-shadow: 0 0 15px #f0f, 0 0 40px #f0f; transform: scale(0.98); }
    55% { text-shadow: 0 0 50px #0ff, 0 0 90px #0ff; transform: scale(1.02); }
    75% { text-shadow: 0 0 10px #0ff; transform: none; }
    90% { text-shadow: none; }
}

/* 7: Horizontal mega tear */
@keyframes glitch-7 {
    0%, 100% { transform: none; text-shadow: none; }
    15% { transform: translateX(22px); text-shadow: -22px 0 #f0f; }
    30% { transform: translateX(-18px); text-shadow: 18px 0 #0ff; }
    45% { transform: translateX(10px); text-shadow: -10px 0 #f0f; }
    60% { transform: translateX(-5px); text-shadow: 5px 0 #0ff; }
    75% { transform: none; text-shadow: none; }
}

/* 8: Scale distort — compress then stretch */
@keyframes glitch-8 {
    0%, 100% { transform: none; }
    20% { transform: scaleX(1.1) scaleY(0.92); }
    35% { transform: scaleX(0.88) scaleY(1.08); }
    50% { transform: scaleX(1.06) scaleY(0.96); }
    65% { transform: scaleX(0.96) scaleY(1.03); }
    80% { transform: none; }
}

/* 9: Ghost double image */
@keyframes glitch-9 {
    0%, 100% { text-shadow: none; }
    20% { text-shadow: 8px 4px 0 rgba(0, 255, 255, 0.55), -8px -4px 0 rgba(255, 0, 255, 0.55); }
    40% { text-shadow: -8px -4px 0 rgba(0, 255, 255, 0.55), 8px 4px 0 rgba(255, 0, 255, 0.55); }
    60% { text-shadow: 4px 8px 0 rgba(0, 255, 255, 0.35), -4px 0 0 rgba(255, 0, 255, 0.35); }
    80% { text-shadow: none; }
}

/* 10: Data corrupt — rapid multi-colour chaos */
@keyframes glitch-10 {
    0%, 100% { transform: none; text-shadow: none; filter: none; }
    10% { transform: translateX(-6px) skewX(6deg); text-shadow: 6px 0 #f00; filter: brightness(1.6); }
    20% { transform: translateX(6px) skewX(-6deg); text-shadow: -6px 0 #0f0; filter: brightness(0.7); }
    30% { transform: translateX(-4px) skewY(3deg); text-shadow: 4px 0 #00f; filter: none; }
    40% { transform: translateX(4px) skewY(-3deg); text-shadow: -4px 0 #f0f; filter: contrast(2); }
    50% { transform: translateX(-3px); text-shadow: 3px 0 #0ff; filter: brightness(1.3); }
    60% { transform: translateX(3px); text-shadow: -3px 0 #ff0; filter: none; }
    70% { transform: none; text-shadow: none; filter: none; }
}

/* Neon flicker on heading */
.neon-header {
    color: #f8fafc;
    animation: neon-flicker 7s infinite;
}

@keyframes neon-flicker {
    0%, 19%, 21%, 23%, 25%, 54%, 56%, 100% {
        color: #f8fafc;
        text-shadow:
            0 0 10px rgba(248, 250, 252, 0.4),
            0 0 30px rgba(248, 250, 252, 0.2),
            0 0 60px rgba(148, 163, 184, 0.15);
    }
    20%, 24%, 55% {
        color: rgba(248, 250, 252, 0.6);
        text-shadow: none;
    }
    22% {
        color: rgba(248, 250, 252, 0.85);
        text-shadow: 0 0 5px rgba(248, 250, 252, 0.2);
    }
}

/* Techy terminal glow on the cycling prefix */
.tech-prefix {
    color: #22d3ee;
    text-shadow:
        0 0 18px rgba(34, 211, 238, 0.5),
        0 0 40px rgba(34, 211, 238, 0.2);
    animation: tech-flicker 5s infinite;
}

@keyframes tech-flicker {
    0%,
    93%,
    100% {
        opacity: 1;
        text-shadow:
            0 0 18px rgba(34, 211, 238, 0.5),
            0 0 40px rgba(34, 211, 238, 0.2);
    }
    94% {
        opacity: 0.65;
        text-shadow: 0 0 4px rgba(34, 211, 238, 0.2);
    }
    95% {
        opacity: 1;
        text-shadow:
            0 0 28px rgba(34, 211, 238, 0.7),
            0 0 60px rgba(34, 211, 238, 0.35);
    }
    96% {
        opacity: 0.8;
    }
}

/* Bouncing scroll arrow */
.bounce-arrow {
    animation: bounce-down 2s ease-in-out infinite;
}

@keyframes bounce-down {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(7px);
    }
}


</style>

<script lang="ts">
export type SceneVariant = 'hero' | 'features' | 'cta';
</script>

<script setup lang="ts">
defineProps<{
    /** Which section variant to render. Each has its own spatial composition. */
    variant: SceneVariant;
}>();
</script>

<template>
    <div class="scene-root">

        <!-- ── Hero — deep space: drifting nebula orbs + layered star field ── -->
        <template v-if="variant === 'hero'">
            <div class="star-field" />
            <div class="star-field star-field--offset" />
            <!-- Neon-cyan nebula bloom, top-centre -->
            <div class="orb orb--neon" style="width:680px;height:680px;top:-200px;left:50%;transform:translateX(-55%);animation-duration:14s;" />
            <!-- Crimson heat source, bottom-right -->
            <div class="orb orb--theme" style="width:440px;height:440px;bottom:-100px;right:-80px;animation-delay:-6s;animation-duration:17s;" />
            <!-- Pale blue ambient, left edge -->
            <div class="orb orb--pale" style="width:270px;height:270px;top:38%;left:-70px;animation-delay:-3s;animation-duration:11s;" />
        </template>

        <!-- ── Features — orbital system: rings + depth orbs + sparse field ── -->
        <template v-else-if="variant === 'features'">
            <div class="star-field star-field--sparse" />
            <!-- Outer & inner rings, counter-rotating, 3D perspective tilt -->
            <div class="orbital-ring" style="width:620px;height:230px;top:48%;left:50%;transform:translate(-50%,-50%) rotate3d(1,0,0,70deg);" />
            <div class="orbital-ring" style="width:390px;height:145px;top:48%;left:50%;transform:translate(-50%,-50%) rotate3d(1,0,0,70deg);animation-duration:22s;animation-direction:reverse;" />
            <div class="orb orb--neon" style="width:500px;height:500px;top:15%;left:-100px;animation-delay:-4s;animation-duration:16s;" />
            <div class="orb orb--pale" style="width:360px;height:360px;bottom:-80px;right:-50px;animation-delay:-9s;animation-duration:13s;" />
        </template>

        <!-- ── CTA — aurora horizon: flowing light bands + dense star field ─── -->
        <template v-else>
            <div class="star-field star-field--dense" />
            <div class="aurora" />
            <!-- Neon bloom, top-right corner -->
            <div class="orb orb--neon" style="width:560px;height:560px;top:-120px;right:-100px;animation-delay:-2s;animation-duration:15s;" />
            <!-- Theme glow rising from centre-bottom -->
            <div class="orb orb--theme" style="width:420px;height:420px;bottom:-100px;left:50%;transform:translateX(-50%);animation-delay:-7s;animation-duration:18s;" />
        </template>

    </div>
</template>

<style scoped>
/* Root fills the section, sits behind all content, clips overflowing orbs. */
.scene-root {
    pointer-events: none;
    position: absolute;
    inset: 0;
    z-index: -10;
    overflow: hidden;
}

/* ── Star fields ─────────────────────────────────────────────────────────── */
/*
 * 25 radial-gradient dots tiled over a 550×550px repeat area.
 * `--foreground` makes them dark specks in light mode, bright stars in dark.
 * Opacities are overridden by the .dark selector — no JS dependency.
 */
.star-field {
    position: absolute;
    inset: 0;
    opacity: 0.04;
    background-image:
        radial-gradient(1px 1px at  23px  67px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at  89px  12px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 178px 145px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 234px  89px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 312px  23px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at  45px 234px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 156px 267px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 267px 178px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 345px 312px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 123px 356px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 389px  45px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at  56px 389px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 200px 412px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 423px 178px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at  78px 300px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 312px 456px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 156px 512px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 489px 267px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at  34px 178px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 267px 534px, var(--foreground) 0%, transparent 100%),
        radial-gradient(2px 2px at 534px  89px, var(--foreground) 0%, transparent 100%),
        radial-gradient(2px 2px at 145px 445px, var(--foreground) 0%, transparent 100%),
        radial-gradient(2px 2px at 378px 356px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 489px 512px, var(--foreground) 0%, transparent 100%),
        radial-gradient(1px 1px at 223px 223px, var(--foreground) 0%, transparent 100%);
    background-size: 550px 550px;
    background-repeat: repeat;
    animation: star-drift 90s linear infinite;
}
:global(.dark) .star-field { opacity: 0.32; }

/* Second layer drifts in opposite direction and phase — depth parallax */
.star-field--offset {
    opacity: 0.02;
    background-size: 720px 720px;
    animation-duration: 130s;
    animation-direction: reverse;
    animation-delay: -45s;
}
:global(.dark) .star-field--offset { opacity: 0.15; }

/* Larger tile = sparser field (Features) */
.star-field--sparse {
    background-size: 750px 750px;
    animation-duration: 110s;
}

/* Smaller tile = denser field (CTA) */
.star-field--dense {
    background-size: 380px 380px;
    animation-duration: 65s;
}

/* ── Orbs ────────────────────────────────────────────────────────────────── */

.orb {
    position: absolute;
    border-radius: 9999px;
    filter: blur(90px);
    opacity: 0.05;
    animation: orb-float 12s ease-in-out infinite;
}
:global(.dark) .orb { opacity: 0.18; }

.orb--neon  { background: var(--color-neon); }
.orb--theme { background: var(--color-theme); }
.orb--pale  { background: hsl(214 80% 80%); }

/* ── Orbital rings (Features) ────────────────────────────────────────────── */

.orbital-ring {
    position: absolute;
    border-radius: 50%;
    border: 1px solid var(--color-neon);
    background: transparent;
    opacity: 0.04;
    animation: ring-spin 28s linear infinite;
}
:global(.dark) .orbital-ring { opacity: 0.14; }

/* ── Aurora band (CTA) ───────────────────────────────────────────────────── */

.aurora {
    position: absolute;
    inset-x: 0;
    top: 0;
    height: 55%;
    background: linear-gradient(
        180deg,
        color-mix(in srgb, var(--color-neon)   22%, transparent)  0%,
        color-mix(in srgb, var(--color-theme)    8%, transparent) 55%,
        transparent 100%
    );
    filter: blur(50px);
    opacity: 1;
    animation: aurora-wave 11s ease-in-out infinite;
}
:global(.dark) .aurora { opacity: 1; }

/* ── Keyframes ───────────────────────────────────────────────────────────── */

@keyframes orb-float {
    0%,  100% { transform: translateY(0) scale(1); }
    50%        { transform: translateY(-24px) scale(1.04); }
}

@keyframes ring-spin {
    from { rotate: 0deg; }
    to   { rotate: 360deg; }
}

@keyframes aurora-wave {
    0%,  100% { transform: scaleX(1) scaleY(1); }
    50%        { transform: scaleX(1.05) scaleY(1.15); }
}

@keyframes star-drift {
    from { background-position: 0 0; }
    to   { background-position: 550px 0; }
}
</style>

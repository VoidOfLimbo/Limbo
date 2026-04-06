# CSS Animations — Learned Patterns

## text-shadow glow clipped by overflow-hidden
- `overflow-hidden` on a container clips `text-shadow` and `filter: blur()` halos outside the box
- Fix: remove `overflow-hidden` and use padding instead to give the glow room
- If you need to constrain height for a sliding transition, use `clip-path` or accept the clip on the transition container only — not on the glow element itself

## Triggered vs continuous animations
- For "fire and forget" effects (glitch on event), use a CSS class added/removed via JS
- Add class → CSS `animation: name Xs forwards` plays once → remove class after duration
- Use `requestAnimationFrame` to ensure the class removal/re-add cycle completes before re-adding (prevents animation not restarting):
```ts
glitchVariant.value = 0;
requestAnimationFrame(() => {
    glitchVariant.value = pickRandom();
    setTimeout(() => { glitchVariant.value = 0; }, durationMs);
});
```
- `forwards` fill mode means element keeps end state; clearing the class resets it

## 10 glitch animation variants (CSS-only)
1. RGB split + skew — `translateX` + `text-shadow: -Xpx 0 #f0f, Xpx 0 #0ff`
2. Micro shake — rapid ±Xpx translateX steps
3. Clip-path slice — `clip-path: polygon(...)` horizontal tears
4. Blur burst — `filter: blur() brightness()`
5. Invert flash — `filter: invert(1)` + `hue-rotate()`
6. Neon pulse — expanding `text-shadow` glow + `scale()`
7. Mega tear — large ±20px translateX with opposite-side shadow
8. Scale distort — `scaleX/scaleY` squeeze and stretch
9. Ghost double — `text-shadow` offsets with rgba colours
10. Data corrupt — per-frame colour chaos `#f00 #0f0 #00f #f0f #0ff #ff0`

## Neon sign flicker (realistic)
- Use irregular keyframe stops: `0%, 19%, 21%, 23%, 25%, 54%, 56%, 100%` = on; `20%, 24%, 55%` = dim
- Vary opacity AND text-shadow together for realism
- Slow cycle (7s+) feels more like a real neon tube

## Tech terminal glow
- Cyan `#22d3ee` with layered `text-shadow`: tight inner glow + wide outer bloom
- Flicker near end of cycle (93–96%) rather than beginning — feels like warm-up

## clip-path with animations
- `clip-path: none` doesn't interpolate — jump-cut only (intentional for glitch)
- Animating `clip-path: polygon(...)` between two states does interpolate smoothly

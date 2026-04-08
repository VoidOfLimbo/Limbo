# Vue Composables — Learned Patterns

## When to extract into a composable
- Extract when logic owns its own reactive state + lifecycle cleanup + is usable in >1 place
- Don't extract if it's just 2-3 lines with no state (inline is fine)
- Don't extract CSS-class-specific logic (e.g. glitch variant toggling tied to local CSS classes)

## requestAnimationFrame composable pattern
- Store raf handle as a local `number | null` (not a ref — no reactivity needed)
- Cancel in `onBeforeUnmount` inside the composable itself — callers don't need to worry about cleanup
- Cancel before starting a new raf loop (guard against overlapping calls)

```ts
let rafHandle: number | null = null;

onBeforeUnmount(() => {
    if (rafHandle !== null) cancelAnimationFrame(rafHandle);
});
```

## Text Scramble composable (useTextScramble)
- Expose `{ scrambled: Ref<string>, scramble: (target: string) => void }`
- Queue-based: each character gets a random `start` and `end` frame
- Between start/end frames: show random glyph from charset
- After end frame: resolve to real character
- Runs entirely on `requestAnimationFrame` — smooth 60fps, no setInterval needed
- Initial value passed as argument: `useTextScramble('By the')`

## Naming convention
- File: `useCamelCase.ts` in `resources/js/composables/`
- Export: named `export function useCamelCase()`
- Return type: explicit named type `UseXxxReturn`

## NeonText — per-character animation component
- Use a component (not a composable) when the logic is inseparable from a template
- Split text into `[...text].map()` — spread operator handles emoji and multi-byte chars correctly
- Use the **golden ratio** (`i * 1.618034 % spread`) for delay distribution — never clumps, looks random but is deterministic (no hydration mismatch)
- Space chars → `\u00A0` (non-breaking space) to preserve layout in `inline-block` spans
- Set `aria-label` on the wrapper and `aria-hidden` on each char span for screen reader compatibility
- `animation-duration` per character prevents all characters from firing simultaneously

## ESLint padding-line-between-statements (Vue/TS projects)
- Blank line required before `return` that follows a statement (e.g. after `complete++`)
- Blank line required before `if` that follows a non-block statement
- Pattern: whenever you increment/assign then immediately return, add blank line between them

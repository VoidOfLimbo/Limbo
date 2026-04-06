import { onBeforeUnmount, ref } from 'vue';
import type { Ref } from 'vue';

type ScrambleQueueItem = { from: string; to: string; start: number; end: number };

const SCRAMBLE_CHARS = '!<>-_\\/[]{}—=+*^?#@$%&|~`';

export type UseTextScrambleReturn = {
    scrambled: Ref<string>;
    scramble: (target: string) => void;
};

export function useTextScramble(initial: string = ''): UseTextScrambleReturn {
    const scrambled = ref(initial);
    let rafHandle: number | null = null;

    function scramble(target: string): void {
        if (rafHandle !== null) {
            cancelAnimationFrame(rafHandle);
            rafHandle = null;
        }

        const current = scrambled.value;
        const length = Math.max(target.length, current.length);
        let frame = 0;

        const queue: ScrambleQueueItem[] = Array.from({ length }, (_, i) => ({
            from: i < current.length ? current[i] : '',
            to: i < target.length ? target[i] : '',
            start: Math.floor(Math.random() * 10),
            end: Math.floor(Math.random() * 10) + 10,
        }));

        const update = (): void => {
            let complete = 0;

            scrambled.value = queue
                .map(({ from, to, start, end }) => {
                    if (frame >= end) {
                        complete++;

                        return to;
                    } else if (frame >= start) {
                        return SCRAMBLE_CHARS[Math.floor(Math.random() * SCRAMBLE_CHARS.length)];
                    } else {
                        return from;
                    }
                })
                .join('');

            frame++;

            if (complete < queue.length) {
                rafHandle = requestAnimationFrame(update);
            } else {
                scrambled.value = target;
                rafHandle = null;
            }
        };

        rafHandle = requestAnimationFrame(update);
    }

    onBeforeUnmount(() => {
        if (rafHandle !== null) {
            cancelAnimationFrame(rafHandle);
        }
    });

    return { scrambled, scramble };
}

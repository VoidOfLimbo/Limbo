<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Card, CardContent } from '@/components/ui/card';
import { home, privacyPolicy, termsOfService } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center bg-muted p-6 md:p-10">
        <div class="w-full max-w-sm md:max-w-4xl">
            <Card class="overflow-hidden p-0">
                <CardContent class="grid p-0 md:grid-cols-2">
                    <!-- Branded left panel -->
                    <div class="relative hidden h-full flex-col bg-zinc-900 p-10 text-white md:flex">
                        <div class="absolute inset-0 bg-zinc-900" />
                        // TODO: Add some cosmic SVG illustrations here that represents shiva, cosmic consciousness, voidoflimbo, etc.
                    </div>

                    <!-- Form panel -->
                    <div class="flex flex-col justify-center p-6 md:p-10">
                        <div class="mx-auto flex w-full flex-col justify-center space-y-6">
                            <div class="flex items-center justify-center">
                                <Link :href="home()" class="relative z-20 flex items-center text-lg font-medium">
                                    <div class="mr-2 flex items-center justify-center rounded-md bg-white p-1">
                                        <AppLogoIcon class="size-8" />
                                    </div>
                                    {{ name }}
                                </Link>
                            </div>
                            <div v-if="title || description" class="flex flex-col space-y-2 text-center">
                                <h1 v-if="title" class="text-2xl font-bold tracking-tight">
                                    {{ title }}
                                </h1>
                                <p v-if="description" class="text-sm text-muted-foreground">
                                    {{ description }}
                                </p>
                            </div>
                            <slot />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
        <p class="px-6 pt-2.5 text-center text-xs text-muted-foreground">
            By submitting this form, you agree to our
            <Link :href="termsOfService()" class="underline underline-offset-4 transition-colors hover:text-foreground">
                Terms of Service</Link>
            and
            <Link :href="privacyPolicy()" class="underline underline-offset-4 transition-colors hover:text-foreground">
                Privacy Policy</Link>.
        </p>
    </div>
</template>

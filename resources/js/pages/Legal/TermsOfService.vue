<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
</script>

<template>
    <Head title="Terms of Service — VoidOfLimbo" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Nav -->
        <header class="sticky top-0 z-50 border-b border-border/40 bg-background/80 backdrop-blur-sm">
            <div class="mx-auto flex h-14 max-w-4xl items-center justify-between px-6">
                <div class="flex items-center gap-3">
                    <Link :href="home()">
                        <Button variant="ghost" size="sm" class="gap-2 text-muted-foreground">
                            <ArrowLeftIcon class="size-4" />
                            Back
                        </Button>
                    </Link>
                    <div class="h-4 w-px bg-border" />
                    <div class="flex items-center gap-2">
                        <div class="flex items-center justify-center rounded-sm dark:bg-white dark:p-0.5">
                            <AppLogoIcon className="size-5" />
                        </div>
                        <span class="text-xs font-medium text-muted-foreground">VoidOfLimbo</span>
                    </div>
                </div>
                <ThemeToggle />
            </div>
        </header>

        <!-- Content -->
        <main class="mx-auto max-w-4xl px-6 py-16 lg:py-24">
            <!-- Page Header -->
            <div class="mb-16 border-b border-border/60 pb-10">
                <p class="mb-3 text-sm font-medium text-primary">Legal</p>
                <h1 class="text-4xl font-bold tracking-tight lg:text-5xl">Terms of Service</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    These terms govern your use of VoidOfLimbo. By using the platform, you agree to be bound by them.
                </p>
                <p class="mt-4 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">Effective date:</span> January 1, 2025 &nbsp;·&nbsp;
                    <span class="font-medium text-foreground">Last updated:</span> January 1, 2025
                </p>
            </div>

            <!-- Sections -->
            <div class="space-y-14">
                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">1. Acceptance of Terms</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            By creating an account or using VoidOfLimbo ("we," "our," or "us"), you agree to these
                            Terms of Service. If you do not agree, do not use the platform.
                        </p>
                        <p>
                            We may update these terms from time to time. Continued use of VoidOfLimbo after changes
                            are posted constitutes your acceptance of the revised terms.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">2. Your Account</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            You are responsible for maintaining the confidentiality of your account credentials and for
                            all activity that occurs under your account. You must be at least 13 years old to use
                            VoidOfLimbo.
                        </p>
                        <p>
                            You agree to provide accurate information when registering and to keep it up to date.
                            Accounts found to be using false or misleading information may be suspended or terminated.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">3. Acceptable Use</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>You agree not to use VoidOfLimbo to:</p>
                        <ul class="space-y-2 pl-4">
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Post or distribute content that is unlawful, harmful, abusive, or otherwise objectionable
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Attempt to gain unauthorised access to any part of the platform or other users' accounts
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Transmit spam, malware, or any code designed to disrupt or damage the platform
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Scrape, crawl, or otherwise extract data from VoidOfLimbo without written permission
                            </li>
                        </ul>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">4. Content You Post</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            You retain ownership of content you create and publish on VoidOfLimbo. By posting content,
                            you grant us a limited, non-exclusive licence to store, display, and deliver that content
                            as part of providing the platform.
                        </p>
                        <p>
                            You are solely responsible for the content you post. We reserve the right to remove content
                            that violates these terms or applicable law.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">5. Subscriptions & Billing</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            Certain features on VoidOfLimbo require a paid subscription (Loyalist tier). Subscription
                            details, pricing, and billing cycles will be clearly presented at the point of purchase.
                        </p>
                        <p>
                            Subscriptions renew automatically unless cancelled before the renewal date. Refunds are
                            handled on a case-by-case basis — contact us if you believe you were charged in error.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">6. Termination</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            You may delete your account at any time from your account settings. We may suspend or
                            terminate accounts that violate these terms, with or without prior notice.
                        </p>
                        <p>
                            Upon termination, your access to the platform will cease. Content you have made public may
                            remain visible to others unless you delete it before termination.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">7. Disclaimer & Limitation of Liability</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            VoidOfLimbo is provided "as is" without warranties of any kind. We do not guarantee
                            uninterrupted or error-free operation. To the fullest extent permitted by law, we are not
                            liable for indirect, incidental, or consequential damages arising from your use of the
                            platform.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">8. Contact</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>If you have questions about these terms, please reach out:</p>
                        <div class="rounded-lg border border-border/60 bg-muted/30 p-5 text-sm">
                            <p class="font-medium text-foreground">VoidOfLimbo</p>
                            <p class="mt-1">
                                Email:
                                <a href="mailto:legal@voidoflimbo.app" class="text-primary underline underline-offset-4">
                                    legal@voidoflimbo.app
                                </a>
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

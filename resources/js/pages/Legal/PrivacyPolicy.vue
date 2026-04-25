<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeftIcon } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
</script>

<template>
    <Head title="Privacy Policy — VoidOfLimbo" />

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
                <h1 class="text-4xl font-bold tracking-tight lg:text-5xl">Privacy Policy</h1>
                <p class="mt-4 text-base leading-relaxed text-muted-foreground">
                    Your privacy matters to us. This policy explains what data we collect, how we use it, and the
                    choices you have regarding your information.
                </p>
                <p class="mt-4 text-sm text-muted-foreground">
                    <span class="font-medium text-foreground">Effective date:</span> January 1, 2025 &nbsp;·&nbsp;
                    <span class="font-medium text-foreground">Last updated:</span> January 1, 2025
                </p>
            </div>

            <!-- Sections -->
            <div class="space-y-14">
                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">1. Introduction</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            Welcome to VoidOfLimbo ("we," "our," or "us"). VoidOfLimbo is a community platform that provides page
                            building tools, community servers, and personal productivity features. We are operated by
                            VoidOfLimbo.
                        </p>
                        <p>
                            This Privacy Policy describes how we collect, use, disclose, and safeguard your information
                            when you use our platform at <span class="font-medium text-foreground">voidoflimbo.app</span> and
                            related services. Please read it carefully. By using VoidOfLimbo, you agree to the practices
                            described here.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">2. Information We Collect</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>We collect information in the following ways:</p>
                        <div class="space-y-5 pl-4">
                            <div>
                                <p class="mb-1 font-medium text-foreground">Information you provide directly</p>
                                <p>
                                    When you register, we collect your username, name, email address, and password (or
                                    your linked social account credentials if you sign in via Google or Microsoft). You
                                    may also optionally provide a bio and profile or cover images.
                                </p>
                            </div>
                            <div>
                                <p class="mb-1 font-medium text-foreground">Information collected automatically</p>
                                <p>
                                    We may collect standard server log data, including IP addresses, browser type,
                                    device type, pages visited, and timestamps. This data is used for security,
                                    performance monitoring, and diagnosing issues.
                                </p>
                            </div>
                            <div>
                                <p class="mb-1 font-medium text-foreground">Social authentication data</p>
                                <p>
                                    If you choose to sign in with Google or Microsoft, we receive a limited set of
                                    profile data from those providers (name, email, and a unique identifier). We do not
                                    receive your social account passwords.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">3. How We Use Your Information</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>We use the information we collect to:</p>
                        <ul class="space-y-2 pl-4">
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Create and manage your account and authenticate you securely
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Provide and personalise platform features, including community servers and productivity tools
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Send transactional emails such as email verification, password resets, and account notifications
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Monitor platform security and prevent abuse, fraud, or unauthorised access
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Improve our services and analyse platform usage in aggregate, anonymised form
                            </li>
                        </ul>
                        <p class="pt-1">We do not sell your personal data to third parties.</p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">4. Data Sharing & Disclosure</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            We do not sell, rent, or share your personal information with third parties for their
                            marketing purposes. We may share data only in the following limited circumstances:
                        </p>
                        <ul class="space-y-2 pl-4">
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                <span>
                                    <span class="font-medium text-foreground">Service providers:</span> Trusted
                                    infrastructure partners (e.g., hosting, email delivery) who process data on our
                                    behalf under strict agreements.
                                </span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                <span>
                                    <span class="font-medium text-foreground">Legal obligations:</span> If required by
                                    law, court order, or governmental authority, we may disclose information as
                                    necessary.
                                </span>
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                <span>
                                    <span class="font-medium text-foreground">Safety:</span> To protect the rights,
                                    safety, or property of VoidOfLimbo, our users, or the public.
                                </span>
                            </li>
                        </ul>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">5. Data Security</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            We implement industry-standard security measures to protect your data, including encrypted
                            connections (HTTPS/TLS), hashed password storage, and restricted database access. We also
                            support two-factor authentication (2FA) for additional account protection.
                        </p>
                        <p>
                            No system is completely secure. In the unlikely event of a data breach that affects your
                            rights, we will notify you in accordance with applicable law.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">6. Cookies & Local Storage</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            VoidOfLimbo uses session cookies to keep you signed in and maintain your preferences (such as
                            theme selection). We do not use advertising or tracking cookies. No third-party analytics
                            scripts are loaded on the platform.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">7. Your Rights & Choices</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>Depending on your location, you may have the right to:</p>
                        <ul class="space-y-2 pl-4">
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Access, correct, or delete the personal data we hold about you
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Withdraw consent where processing is based on consent
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Object to or restrict certain types of processing
                            </li>
                            <li class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                                Request a portable copy of your data
                            </li>
                        </ul>
                        <p class="pt-1">
                            To exercise these rights, please contact us at the address below. We will respond within
                            30 days.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">8. Children's Privacy</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            VoidOfLimbo is not directed at children under 13 years of age. We do not knowingly collect
                            personal information from children. If you believe a child has provided us with personal
                            data, please contact us and we will delete it promptly.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">9. Changes to This Policy</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>
                            We may update this Privacy Policy from time to time. When we do, we will revise the "Last
                            updated" date at the top of this page. For significant changes, we may notify you via
                            email or an in-app notice. Continuing to use VoidOfLimbo after changes take effect constitutes
                            your acceptance of the updated policy.
                        </p>
                    </div>
                </section>

                <section>
                    <h2 class="mb-4 text-xl font-semibold tracking-tight">10. Contact Us</h2>
                    <div class="space-y-3 text-sm leading-7 text-muted-foreground">
                        <p>If you have questions, concerns, or requests regarding this Privacy Policy, please reach out:</p>
                        <div class="mt-2 rounded-xl border border-border/60 bg-card p-5">
                            <p class="font-medium text-foreground">VoidOfLimbo</p>
                            <p class="mt-1">
                                Email:
                                <a href="mailto:privacy@voidoflimbo.app" class="text-primary underline-offset-4 hover:underline">
                                    privacy@voidoflimbo.app
                                </a>
                            </p>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Footer note -->
            <div class="mt-20 border-t border-border/40 pt-8 text-center text-xs text-muted-foreground">
                <p>© {{ new Date().getFullYear() }} VoidOfLimbo. All rights reserved.</p>
                <p class="mt-1">
                    <Link :href="home()" class="underline-offset-4 hover:underline">Return to home</Link>
                </p>
            </div>
        </main>
    </div>
</template>

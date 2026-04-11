# App State — VoidOfLimbo

Current snapshot of what exists in the codebase. Updated manually as features are built.

---

## Tech Stack

| Layer | Package | Version |
|---|---|---|
| Runtime | PHP | 8.4 |
| Framework | Laravel | 13 |
| SPA bridge | Inertia.js (Laravel + Vue) | v3 |
| Frontend | Vue 3 | 3.x |
| Styling | Tailwind CSS | v4 |
| Auth backend | Laravel Fortify | v1 |
| Social auth | Laravel Socialite | v5 |
| Typed routes | Laravel Wayfinder | v0 |
| Testing | Pest | v4 |
| Dev server | Laravel Sail | v1 |
| Code style | Laravel Pint | v1 |

---

## Database Schema

### `users`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `username` | string | unique |
| `name` | string | display name |
| `email` | string | unique |
| `email_verified_at` | timestamp | nullable |
| `password` | string | nullable — OAuth-only users have no password |
| `bio` | string(500) | nullable |
| `tier` | string | `free` / `premium` / `loyalist` (default: `free`) |
| `profile_image` | string | nullable |
| `profile_image_fallback` | string | nullable |
| `cover_image` | string | nullable |
| `cover_image_fallback` | string | nullable |
| `remember_token` | string | nullable |
| `two_factor_secret` | text | nullable (Fortify 2FA) |
| `two_factor_recovery_codes` | text | nullable (Fortify 2FA) |
| `two_factor_confirmed_at` | timestamp | nullable (Fortify 2FA) |
| `created_at` / `updated_at` | timestamps | |

### `social_accounts`
| Column | Type | Notes |
|---|---|---|
| `id` | ULID (PK) | |
| `user_id` | string(26) FK → `users.id` | cascade delete |
| `provider` | string | e.g. `google`, `microsoft` |
| `provider_id` | string | provider's user ID |
| `provider_token` | text | nullable |
| `provider_refresh_token` | text | nullable |
| `created_at` / `updated_at` | timestamps | |
| — | unique | `(provider, provider_id)` |

### Standard Laravel tables
`password_reset_tokens`, `sessions`, `cache`, `jobs`

---

## Models

### `User`
- Traits: `HasUlids`, `HasFactory`, `Notifiable`, `TwoFactorAuthenticatable`
- Implements: `MustVerifyEmail`
- Fillable: `username`, `name`, `email`, `password`, `bio`, `tier`, `profile_image`, `profile_image_fallback`, `cover_image`, `cover_image_fallback`
- Hidden: `password`, `two_factor_secret`, `two_factor_recovery_codes`, `remember_token`
- Casts: `email_verified_at` → datetime, `password` → hashed, `two_factor_confirmed_at` → datetime, `tier` → `UserTier`
- Relations: `hasMany(SocialAccount::class)`

### `SocialAccount`
- Traits: `HasUlids`, `HasFactory`
- Fillable: `user_id`, `provider`, `provider_id`, `provider_token`, `provider_refresh_token`
- Relations: `belongsTo(User::class)`

---

## Enums

### `UserTier: string`
| Case | Value |
|---|---|
| `Free` | `'free'` |
| `Premium` | `'premium'` |
| `Loyalist` | `'loyalist'` |

---

## Authentication

Powered by **Fortify** (backend) + **Socialite** (OAuth).

- Login identifier: `email`
- Post-auth redirect: `/dashboard`
- Usernames are lowercased before save

**Fortify features enabled:** registration, password reset, email verification, 2FA (TOTP)

**OAuth providers configured:** Google, Microsoft

**Fortify Actions** (`app/Actions/Fortify/`): CreateNewUser, UpdateUserProfileInformation, UpdateUserPassword, ResetUserPassword

---

## Routes

### Public
| Method | Path | Page / Handler |
|---|---|---|
| GET | `/` | `Welcome` |
| GET | `/privacy-policy` | `Legal/PrivacyPolicy` |
| GET | `/invite` | `Invite` |

### Auth-only (`auth` middleware)
| Method | Path | Handler |
|---|---|---|
| GET | `/settings` | redirect → `/settings/profile` |
| GET | `/settings/profile` | `ProfileController@edit` |
| PATCH | `/settings/profile` | `ProfileController@update` |

### Auth + verified (`auth`, `verified`)
| Method | Path | Handler |
|---|---|---|
| GET | `/dashboard` | `Dashboard` |
| DELETE | `/settings/profile` | `ProfileController@destroy` |
| GET | `/settings/security` | `SecurityController@edit` |
| PUT | `/settings/password` | `SecurityController@update` (throttle 6/min) |
| GET | `/settings/appearance` | `settings/Appearance` |

Fortify registers its own auth routes (login, register, logout, password, 2FA, email verify).

---

## Pages (`resources/js/pages/`)

| Page | Route | Auth? |
|---|---|---|
| `Welcome.vue` | `/` | no |
| `Dashboard.vue` | `/dashboard` | yes + verified |
| `Invite.vue` | `/invite` | no |
| `Legal/PrivacyPolicy.vue` | `/privacy-policy` | no |
| `auth/Login.vue` | `/login` | — |
| `auth/Register.vue` | `/register` | — |
| `auth/ForgotPassword.vue` | `/forgot-password` | — |
| `auth/ResetPassword.vue` | `/reset-password` | — |
| `auth/ConfirmPassword.vue` | `/confirm-password` | — |
| `auth/TwoFactorChallenge.vue` | `/two-factor-challenge` | — |
| `auth/VerifyEmail.vue` | `/verify-email` | — |
| `settings/Profile.vue` | `/settings/profile` | yes |
| `settings/Security.vue` | `/settings/security` | yes + verified |
| `settings/Appearance.vue` | `/settings/appearance` | yes + verified |

---

## Components (`resources/js/components/`)

### Animation / visual effects
| Component | Purpose |
|---|---|
| `NeonText.vue` | Per-character CSS @keyframe neon sign flicker. Supports per-char tilt, colour, flicker rate. Props: `text`, `tag`, `color`, `spread`, `tilt[]`, `flicker[]`, `defaultNeonColor`, `defaultMinFlickers`, `defaultMaxFlickers`, `defaultInterval`, `defaultSpeed` |
| `GlitchText.vue` | Glitch distortion effect on text. Exposes `trigger()` ref method |
| `ScrambleText.vue` | Cycles through a list of strings with a character-scramble transition. Emits `@change` |
| `BounceWrapper.vue` | Wraps content with a bounce-in animation |

### Navigation / layout
| Component | Purpose |
|---|---|
| `AppShell.vue` | Root shell wrapper |
| `AppHeader.vue` | Top navigation bar |
| `AppSidebar.vue` | Sidebar navigation |
| `AppSidebarHeader.vue` | Sidebar logo / title area |
| `AppContent.vue` | Main content wrapper |
| `AppLogo.vue` | Full logo (icon + wordmark) |
| `AppLogoIcon.vue` | Icon-only logo |
| `NavMain.vue` | Primary nav link list |
| `NavFooter.vue` | Sidebar bottom links |
| `NavUser.vue` | User avatar + name in nav |
| `Breadcrumbs.vue` | Breadcrumb trail |
| `DynamicFloatingMenu.vue` | Floating dock with position (`top`/`bottom`/`left`/`right`), alignment, collapsible toggle, active item highlight, and `@select` emit |
| `ThemeToggle.vue` | Light / dark / system theme switcher |

### Auth / user
| Component | Purpose |
|---|---|
| `UserInfo.vue` | Displays user avatar + name |
| `UserMenuContent.vue` | Dropdown menu content for logged-in user |
| `DeleteUser.vue` | Account deletion confirmation UI |
| `TwoFactorSetupModal.vue` | QR code + TOTP confirmation modal |
| `TwoFactorRecoveryCodes.vue` | Displays and regenerates 2FA recovery codes |

### UI primitives
| Component | Purpose |
|---|---|
| `AlertError.vue` | Error alert banner |
| `InputError.vue` | Inline field validation message |
| `PasswordInput.vue` | Password field with show/hide toggle |
| `Heading.vue` | Semantic section heading |
| `TextLink.vue` | Styled anchor |
| `AppearanceTabs.vue` | Theme switcher tabs |
| `PlaceholderPattern.vue` | Decorative background pattern |
| `ui/` | shadcn-vue primitives (Badge, Button, etc.) |

---

## Composables (`resources/js/composables/`)

| Composable | Purpose |
|---|---|
| `useAppearance` | Reads/writes light/dark/system preference |
| `useCurrentUrl` | Reactive current URL |
| `useInitials` | Derives initials from a name string |
| `useTextScramble` | Drives the character-scramble animation used by `ScrambleText` |
| `useTwoFactorAuth` | Manages 2FA setup state (QR fetch, confirmation, recovery codes) |

---

## Layouts (`resources/js/layouts/`)

| Layout | Used by |
|---|---|
| `AppLayout.vue` | Authenticated app pages |
| `AuthLayout.vue` | Auth pages (login, register, etc.) |
| `app/` | Sub-layouts for app shell variants |
| `auth/` | Sub-layouts for auth page variants |
| `settings/` | Settings page wrapper |

---

## Welcome Page — Current State

A single-page marketing/landing experience with three scroll sections:

### Hero
- **NeonText** "VoidOfLimbo" — `#aa00ff`, `L` tilted 18°
- **ScrambleText** cycling "By the / For the / Of the" — triggers glitch on change
- **GlitchText** "Developer"
- Tagline paragraph
- CTA buttons: "Create Free Account" (conditional on `canRegister`), "Sign In"
- **DynamicFloatingMenu** anchored bottom-center, collapsible, tracks active section via IntersectionObserver

### Features
6 cards:
1. Page Builder (Servers)
2. Community Servers (Community)
3. Expense Planner (Productivity)
4. Life Planner (Productivity)
5. Granular Access Control (Access)
6. Loyalist Perks (Premium)

### CTA
Join / pricing call-to-action (section exists, content TBD).

---

## Actions (`app/Actions/Fortify/`)

Standard Fortify action stubs, customized for this app:
- `CreateNewUser` — validates username + email, creates user with `Free` tier
- `UpdateUserProfileInformation` — updates name, username, email (triggers re-verification on email change)
- `UpdateUserPassword` — validates current + new password
- `ResetUserPassword` — sets new password after token verification

---

## What Doesn't Exist Yet

- Any real dashboard content
- Server / community feature (Page Builder)
- Expense Planner module
- Life Planner module
- Subscription / payment flow
- Loyalist perks implementation
- Invite system (page exists, logic TBD)
- Public user profiles
- OAuth callback controllers (Socialite routes not yet wired)

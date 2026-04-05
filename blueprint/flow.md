# Auth & Access Flows

---

## Registration

### Landing Page
A user navigates to the landing page (`/`) and sees a brief overview of the platform. They see the option to enter an invite code or sign up for a new account. Scrolling reveals more details — featured tools, public pages, and servers.

---

### Self Registration
1. User clicks **"Sign Up for Free"** and is taken to `/register`.
2. They enter their **email address**, **password**, and choose a **username**.
3. Alternatively, they can sign up via **Google**, **Outlook**, or **Apple** for a streamlined flow.
4. Once all required fields are filled and the terms of service are accepted, the **Register** button becomes active.
5. On success, the account is created and the user is redirected to the dashboard.

> After registration, an email verification link is sent — see [Email Verification](#email-verification) below.

---

### Invite Flow
1. User enters an invite code on `/` or navigates directly to `/invite`.
2. Upon entering a valid code, they are taken to `/register` with the invite code pre-filled.
3. The registration process follows the [Self Registration](#self-registration) flow.
4. On success, the user is **automatically added to the server** associated with the invite code, with access governed by that server's visibility settings.

---

## Login

### Standard Login
1. User navigates to `/login` and enters their **email** and **password**.
2. If credentials are valid:
   - User is redirected to `/dashboard`, **or**
   - If 2FA is enabled, user is redirected to the [2FA Challenge](#2fa-challenge) page first.
3. If credentials are invalid, an error is shown and the user may try again.

> Users who registered via a social provider (Google, Outlook, Apple) log in through that provider and are not prompted for a password.

---

### Password Reset
1. User clicks **"Forgot your password?"** on the login page → taken to `/forgot-password`.
2. They enter their registered email address and submit.
3. If the email exists, a **password reset link** is sent to that address.
4. Clicking the link opens `/reset-password` where they set and confirm a new password.
5. On success, user is redirected to `/login` with a confirmation message.

---

### Email Verification
1. After registration, a **verification link** is sent to the registered email.
2. The user is shown a notice page (`/email/verify`) prompting them to check their inbox.
3. Clicking the link confirms email ownership and fully activates the account.
4. If the email hasn't arrived, the user can request a new link from the notice page.

> Some platform features and content may be gated behind email verification.

---

### 2FA Challenge
1. Triggered after a successful credential check when 2FA is enabled on the account.
2. User is redirected to `/two-factor-challenge`.
3. They enter a valid **TOTP code** from their authenticator app, or use a **recovery code**.
4. On success, the session is established and the user is redirected to `/dashboard`.
5. Too many failed attempts will temporarily lock the challenge per the configured rate limiter.

---

## Link / OTP Access

Certain content can be shared via a special invite link (visibility level `link`, as defined in [access.md](access.md)).

1. A visitor follows the shared link.
2. They are presented with an **OTP prompt**.
3. Entering the correct OTP grants **temporary, scoped access** to the linked content — no account required.
4. If the visitor is already authenticated, the OTP step may be skipped if the link is pre-validated.

> This access is strictly scoped to the linked content and does not grant broader platform access.
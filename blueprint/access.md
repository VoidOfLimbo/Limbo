# Visibility & Access Control

---

## Visibility Stack

Content visibility is determined by an ordered stack of access levels. Each content item (page, component, feature) is assigned one level, which determines who can see it. Levels are ordered **lowest → highest**:

| # | Level | Who Can Access |
|---|---|---|
| 1 | `public` | Anyone, including unauthenticated visitors |
| 2 | `platform` | Any authenticated platform user |
| 3 | `chads` | Users with an active subscription to any Server |
| 4 | `followers` | Users following the content owner (user or Server) |
| 5 | `friends` | Users in the content owner's contact list *(not the same as mutual followers)* |
| 6 | `members` | Members of the Server that owns the content |
| 7 | `supporters` | Active subscribers to the owning Server |
| 8 | `moderators` | Moderators of the owning Server |
| 9 | `owner` | The Server owner — always has full access |

> The stack is fully ordered. A user at a higher level automatically satisfies all levels below it. For example, a `members`-level page is accessible to members, supporters, moderators, and the owner — but not to followers or friends.

---

## Out-of-Band Access

These mechanisms sit outside the standard visibility stack and operate independently:

| Mechanism | How It Works |
|---|---|
| `link` | Anyone with the invite link + OTP can access; no authentication required |
| `private` | Only specific platform users explicitly granted access by the owner; tied to individual accounts |
| `superowner` | The platform owner — has access to all content across all servers regardless of any visibility setting |

---

## `only` Mode

From `chads` and above, content owners can use **`only` mode** to restrict access to a single exact level, excluding all others — even those higher in the stack.

**Example:** A page set to `only:supporters` is accessible **only** to active Server subscribers. Moderators and the regular `owner` role cannot access it through the standard stack.

> The `owner` always retains access to all content within their own server, even under `only` mode, and can update visibility settings on any content item.

---

## Special Roles

### Owner
- Always has full access to all content within their own Server, regardless of visibility settings or `only` mode.
- Can update the visibility settings of any content item in their Server.

### Super Owner
- Has access to all servers and all content across the entire platform, regardless of any visibility setting or `only` mode.
- Changes made by the Super Owner **require approval from the Server owner** before taking effect, to preserve server autonomy.
- The Super Owner can **bypass the approval process** in emergencies or for critical platform issues.

---

## Audit Logging & Notifications

All access-related actions are logged for transparency and accountability, including:

- Changes to visibility settings
- Access grants and revocations
- Access attempts by users

**Audit logs** are available to Server owners and the Super Owner, with access controls in place to protect user privacy.

Any **CRUD action** on a content item also triggers notifications to the owner and relevant users based on the content's visibility settings.

---

## Demo Access

Content owners can mark individual pages or features as **demoable**, allowing non-members to preview gated content for a limited time.

- Any user can **request demo access** to a demoable item.
- The Server owner **approves or denies** each request.
- Approval grants a **time-limited access window** — duration and scope configured by the Server owner.
- Demo sessions are **logged** and count toward the server's audit trail.
- When a demo period expires, access **automatically reverts** to the user's standard visibility level.
- The Super Owner can **review and revoke** demo access across any server if required.

---

## Access Tiers

There are 3 platform-level access tiers:

| Tier | How to Get It | Key Capabilities |
|---|---|---|
| **Free** | Default on registration | Public content, profile, follow users & servers |
| **Premium** | One-time Server purchase | Own a Server, invite members, configure subscriptions |
| **Loyalist** | Recurring platform subscription | All Free + Premium benefits, enhanced profile, insider access |

### Free
- Default tier for all registered users.
- Access to all `public` content and the Limbo server.
- Can create and manage a profile, follow users and servers, and interact with public content.
- Can be invited as a member of a Premium server and access its content per that server's visibility settings.

### Premium
- Unlocked by paying a one-time Server creation fee.
- Can create and own a Server, configure pages, set visibility rules, and offer server subscriptions.
- Can invite Free-tier users as server members.

### Loyalist
- Active recurring platform subscription.
- Includes all Free and Premium capabilities.
- Access to enhanced profile features (animated media, special effects, custom overlays).
- Insider community access — early feature previews, direct feedback channel to the development team.
- Exclusive platform-wide content and features not available to Free or Premium tiers.

> `superowner` does not belong to any tier and has the highest level of access across the entire platform.

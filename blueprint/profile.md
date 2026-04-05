# Public Profile

---

## URL & Routing

User profiles are accessible at `/@{username}` (e.g. `/@voidoflimbo`).

The `@` prefix visually distinguishes profile routes from other top-level platform routes and aligns with common social platform conventions (X, Threads, etc.).

---

## Profile Layout

### Header
- **Cover image** — full-width banner
- **Profile image** — avatar, overlapping the bottom edge of the cover
- **Display name** (`name`)
- **Username handle** (`@username`)
- **Bio** — short optional text
- **Member since** date

### Action Bar (when viewing another user's profile)
- **Follow** button — visible to authenticated users
- **Add Friend** button — visible once following (or based on user settings)
- **Message** — future

### Stats
- Follower count
- Following count
- Public server count

---

## Visibility Rules

| Profile Section | Minimum Access Level |
|---|---|
| Header (name, username, avatar, cover) | `public` |
| Bio, stats | `public` |
| Server memberships list | `platform` — only public servers shown |
| Activity feed | Per-item visibility (inherits from content) |

> Unauthenticated visitors see the header and bio only. Full profile (stats, servers) requires authentication (`platform` level).

---

## Image Handling

The platform serves the most appropriate media format based on viewer device capabilities. Format restrictions are enforced at upload time based on the uploader's tier.

| Tier | Allowed Upload Formats |
|---|---|
| `free` | JPEG, PNG, WebP (static only) |
| `premium` | + animated GIF, WebM, MP4 |
| `loyalist` | + special effects, overlays, particle animations |

- Animated uploads automatically generate a static fallback stored in the `*_fallback` column.
- The platform resolves which format to serve — static fallback is used for low-bandwidth connections or devices that don't support dynamic formats.

---

## Edit Profile

Users can edit their own profile at `/settings/profile` (already scaffolded).

Editable fields:
- Display name
- Bio
- Profile image
- Cover image
- *(Future: location, website, social links)*

---

## Build Notes

- Profile route `/@{username}` needs explicit route binding — `username` is not the primary key (ULID), so a `resolveRouteBinding` override or `Route::bind` is needed.
- The `@` character in routes requires care: register as `/{username}` with a prefix, or use `/@{username}` with proper encoding handling.
- Avatar/cover image storage uses Laravel's file storage — paths stored in DB, served via signed URLs or public disk depending on visibility.

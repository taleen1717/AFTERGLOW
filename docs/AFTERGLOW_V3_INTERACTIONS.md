# AFTERGLOW v3 — Social Interactions, Privacy and Acceptance Criteria

Version: 3.0 — November 12, 2026. This supplements the original SRS and v2 requirements.

## Scope and rationale

AFTERGLOW allows community reactions on four kinds of public content: **Quotes**, **Visual Boards**, **Code Exchange snippets**, and **Public songs** in Community Soundtrack. Quote Studio reuses the underlying quote rather than duplicating likes or comments. Daily Glow links directly to its original quote card, where visitors can like or comment. The private library and personal profile settings are not public posts and therefore have no social reactions.

## Common interaction requirements

- FR-V3-01: A signed-in user can like any public post **including their own**. Only one like per user/post is stored. Clicking again unlikes it. Guests can read counts but must sign in to react.
- FR-V3-02: A signed-in user can submit a 1–500-character comment on public content. Comments are escaped on display. The latest five comments appear with author name and avatar; total comment count is shown. No raw HTML or code execution from comments.
- FR-V3-03: Only the author of a comment can delete it; attempts by another user return HTTP 403. Deleting a post deletes all associated likes/comments through foreign keys.
- FR-V3-04: Like and comment submissions are throttled. All modifying routes use CSRF protection and authenticated sessions.
- FR-V3-05: Social components work in both **Cyber Night** and **Icy Ethereal** themes, on desktop and mobile.

## Code Exchange

- FR-V3-06: Every snippet has a like count, a toggleable Like button for members, and a comment thread. Code snippets remain read-only in the gallery; the copy button and owner-only edit/delete remain unchanged.
- Acceptance: Author and visitor can each like the same snippet; second click by the same user removes their like. A guest sees counts and a login prompt. Comments persist after refresh. Other users cannot delete someone else's comment. Deleting the snippet removes its interactions.

## Community Soundtrack and privacy

- FR-V3-07: Public songs have the same like/comment features on the Community Soundtrack page. The owner can see interactions on their own Public song in My Soundtrack.
- FR-V3-08: Private songs must not appear in community discovery and **must not be liked or commented on** through direct endpoint requests, even by the owner; the server returns HTTP 404. A song is private by default and only its owner may change its visibility.
- FR-V3-09: Switching Public → Private immediately hides the song and its reactions from everyone else. Reactions remain stored and are visible again only if the owner later chooses Public. Deleting the song permanently removes its reactions.
- Acceptance: POSTing to a private song's like/comment endpoint returns 404, and no interaction row is inserted. Public songs show counts and comments; 501-character comments are rejected; unauthorized deletion returns 403.

## Automated tests

- `tests/Feature/SocialInteractionTest.php`: Quote/board likes (including self-likes), comments, authorization and cascades.
- `tests/Feature/CodeAndSongInteractionsTest.php`: Code/public-song reactions, private-song access, guest auth, visibility changes, deletion and cascades.
- `tests/Feature/ProfileAndMusicPrivacyTest.php`: Private-by-default songs, profile/avatar and password validation.

## Release safety

1. Keep GitHub **Private** until the entire Git history has been scanned for credentials and the right to publish all media/assets has been checked.
2. Back up the Neon database before migrations. Never copy a local SQLite file over production.
3. Deploy v2 migration `2026_11_12_000001` and then v3 migration `2026_11_12_000002` to staging, and run `php artisan test` with dependencies installed.
4. Test both themes, Code Exchange Copy/Edit/Delete, public/private music, quote/board interactions, and profile uploads on staging before merging to main.
5. Render Free cold-start behavior remains unchanged; no paid upgrade is required.

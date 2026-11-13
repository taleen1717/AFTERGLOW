# AFTERGLOW v2 — Functional Requirements and Acceptance Criteria

Version: 2.0 (November 2026) · Laravel 13 · PostgreSQL / SQLite (testing)

## Overview
AFTERGLOW is a creative community for quotes, music, visuals, and code. The two supported themes are Cyber Night and Icy Ethereal. Existing core functions remain unchanged.

## Social interactions
**FR-S1 — Quote likes:** Authenticated users can like or unlike any quote, including their own. A unique database constraint prevents duplicate likes. Guests are directed to log in. **Acceptance:** a like increases the count once; another click removes it; an own-post like works and is counted once.

**FR-S2 — Quote comments:** Signed-in users can write 1–500 character comments. The latest five comments are visible beneath each quote; counts show all comments. Only the comment author can delete it. Output is HTML-escaped. **Acceptance:** valid comment appears; 501-character comment is rejected; other-user deletion returns HTTP 403.

**FR-S3/S4 — Visual Board likes and comments:** Same authorization, input validation, uniqueness and deletion rules as quotes, including self-likes. A deleted board automatically deletes related comments and likes via foreign keys. **Acceptance:** social interactions work on the gallery and do not allow unauthorized deletion.

## My Soundtrack visibility
**FR-M1 — Choose visibility:** Every song saved through YouTube or Spotify has an explicit visibility choice: Private (default) or Public. Existing songs are migrated as private. **Acceptance:** new song with Private is absent from the community page; Public is visible there.

**FR-M2 — Change visibility:** Only the owner can update a saved song's visibility. **Acceptance:** other users receive HTTP 403 on update; changing Public to Private removes it from community discovery immediately.

**FR-M3 — Public discovery:** Guests and members can browse a paginated list of public songs with artist, mood, sharer and a link to the provider. No private song or linked private data is displayed. Playback depends on Spotify/YouTube restrictions.

## User profile
**FR-P1 — Personal information:** Logged-in users can edit display name (2–50 letters/spaces), email (valid and unique) and optional bio (up to 280 characters). **Acceptance:** updates persist; duplicate email is rejected; changing email resets verification timestamp.

**FR-P2 — Avatar:** Logged-in users can upload or remove a profile photo (JPG, PNG, WEBP, GIF, max 2 MB). Cloudinary is used in hosted production; local public disk in development. The navbar uses the saved avatar. **Acceptance:** valid photo is displayed; oversized upload is rejected; replacement cleans up old storage.

**FR-P3 — Password:** A user must provide the current password and a confirmed new password of at least eight characters. **Acceptance:** wrong current password is rejected; successful update stores a hash, not plaintext.

**FR-P4 — Themes:** Profile and Community Soundtrack pages honor the existing theme toggle and saved preference. **Acceptance:** switching themes changes the page without changing user data.

## Non-functional requirements
- Authorization on all writes; server-side validation; CSRF protection; escaping of user-supplied text.
- Rate limits on comment and like endpoints; cascade cleanup of dependent social records.
- Privacy by default for all previously saved and newly created songs.
- Hosted images remain available across Render free-service restarts through Cloudinary.
- No paid Render upgrade is required. Free hosting may sleep and have a cold start.

## Automated tests
`tests/Feature/SocialInteractionTest.php` and `tests/Feature/ProfileAndMusicPrivacyTest.php` cover authorization, likes, comments, cascades, privacy, profile edits, password and avatars. Run `php artisan test` after `composer install` with a PHP SQLite driver, then manually test Cloudinary and the two themes in a staging environment.

## Deployment checklist
1. Back up the Neon production database; test on staging first.
2. Ensure Render environment secrets remain private; never commit `.env`.
3. Push the updated source to a feature branch, run CI, and review the diff.
4. Deploy only after tests pass; Render startup command runs `php artisan migrate --force`.
5. Confirm old songs remain private, old boards and quotes remain accessible, profile uploads work, and no data is lost.
6. Scan all Git history for secrets before changing GitHub repository visibility to Public.

**v3 extension:** Code Exchange and public songs also support likes and comments. See `AFTERGLOW_V3_INTERACTIONS.md` for the complete rules.

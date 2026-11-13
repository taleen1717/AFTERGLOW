# AFTERGLOW v3 — deployment and public GitHub checklist

**Baseline:** source exported from the user's October 31 AFTERGLOW GitHub ZIP, extended with v2 profile/music/social changes and v3 interactions. Existing production site and GitHub repository have NOT been modified by this archive.

1. Keep a backup of the current project and Neon production database.
2. Extract this archive in a **separate local folder** (not over the currently deployed project). Copy only your own local `.env` into that folder if needed; never upload or commit it.
3. Run `composer install`, `npm ci`, `npm run build`, `php artisan migrate`, `php artisan test` on a local/staging database.
4. Verify: existing accounts and quotes; new profile avatar; both themes; quote/board self-likes and comments; code snippet likes/comments and Copy Code; private/public song visibility and social actions; comment ownership; upload and Cloudinary integration.
5. Use a feature branch on GitHub, compare with current main and open a PR. Merge only after tests and staging work. Check Render's deploy and migration logs after merging.
6. Keep `APP_DEBUG=false`, `APP_ENV=production`; do not expose `.env`, `DB_URL`, `APP_KEY`, Cloudinary credentials or YouTube API keys.
7. **Important finding:** the original source ZIP included a root file named `laravel` which is an SQLite database (including a session record). This v3 archive excludes it and adds it to `.gitignore`, but it may still exist in Git history. Inspect and clean **every commit in Git history** for this database, secrets, and personal information, and check licenses/rights for included media and images. Rotate exposed credentials and purge them from history if necessary.
8. After the audit: GitHub → repository Settings → General → Danger Zone → Change repository visibility → Public.

Note: `php -l` can check PHP syntax but cannot replace a full Laravel feature-test run. The archive does not contain Composer `vendor/`, `node_modules/`, production database, or `.env` secrets.

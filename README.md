<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).


## AFTERGLOW v2 — Community and profiles

- Quote and Visual Board likes and comments (signed-in users; owner-only comment deletion).
- Music privacy: every saved song defaults to Private; owners can opt in to Public community discovery.
- User profile: editable display name, email, bio, avatar and password; Cyber Night / Icy Ethereal themes.
- Database migration: `2026_11_12_000001_add_social_profiles_and_music_visibility.php`.
- Requirements and acceptance criteria: `docs/AFTERGLOW_V2_REQUIREMENTS.md`.
- Tests: `php artisan test`; GitHub Actions runs the test suite on PRs.

**Important:** Back up the production database before deployment, test on a staging service, and never commit `.env` or Cloudinary/Neon keys. All existing songs remain private after migration. Before making the GitHub repository public, scan the full Git history for exposed credentials and verify you have rights to publish all images/assets.


## AFTERGLOW v3 — Social interactions everywhere appropriate

- **Likes and comments** on Quotes, Visual Boards, Code Exchange snippets and **Public** Community Soundtrack songs. Authors can like their own posts.
- Every user has at most one like per post; clicking again removes it. Only comment authors may delete their comments.
- Guest users can read counts and comments, but must sign in to react. Comments are escaped and limited to 500 characters.
- Private songs cannot be viewed or reacted to via social endpoints; existing songs remain private by default. Switching a song private hides existing interactions until it is public again.
- My Soundtrack owners can see reactions to their public songs; private songs show no social controls.
- New migration: `2026_11_12_000002_add_code_and_song_interactions.php` (run after v2 migration). No existing data is dropped.
- Requirements: `docs/AFTERGLOW_V3_INTERACTIONS.md`. Tests: `tests/Feature/CodeAndSongInteractionsTest.php` plus updated quote/board tests.

### Local verification before publishing

```bash
composer install
npm ci
npm run build
php artisan migrate
php artisan test
```

Run the commands in a safe local/staging environment with a backup. **Do not deploy this archive directly to Render** until the tests and manual acceptance checks pass. Keep GitHub Private until a full-history secrets and asset-rights audit is complete.

### Public repository safety note

The original exported project contained a root-level file named `laravel` which was an **SQLite database**, not source code. It has been deliberately **excluded** from this v3 source archive. The old Git history may still contain that database (including a session record); do not switch the repository to Public before checking and cleaning its history. `.gitignore` now excludes this file for future changes.

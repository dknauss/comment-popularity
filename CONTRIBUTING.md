Contributing guidelines [![Build Status](https://travis-ci.org/humanmade/comment-popularity.svg?branch=master)](https://travis-ci.org/humanmade/comment-popularity)
=======================

Canonical workflow policy: see `FORK_FIRST_POLICY.md`. If this file and another document conflict, follow `FORK_FIRST_POLICY.md`.

Coding Standards
----------------

Please follow the [WordPress Coding Standards](http://make.wordpress.org/core/handbook/coding-standards/)

Quality baseline (local + CI)
-----------------------------

From a clean checkout:

1. `composer install --no-interaction --prefer-dist`
2. `composer lint`
3. `WP_VERSION=6.4 composer test:setup`
4. `composer test`
5. `composer test:integration`
6. `WP_MULTISITE=1 composer test:integration`
7. `composer test:coverage`
8. `composer test:phpstan`
9. `composer test:psalm`
10. `composer test:local-smoke`

CI uses the same Composer scripts for consistency. PHP `8.2+` is required for this repository.
`composer test:setup` now resets and recreates the test database each run to keep test state deterministic.
`bin/php-runtime.sh` now routes local CLI quality commands to a compatible PHP runtime and enforces `8.2+` (prefers Local PHP `8.4`/`8.3`/`8.2` when host `php` is older). Override with `CP_PHP_BIN=/path/to/php` if needed.

Coverage and hardening notes
----------------------------

- Coverage scope excludes vendored dependencies under `inc/lib`.
- `composer test:coverage` uses `phpdbg`, so no Xdebug/PCOV setup is required.
- Coverage threshold is enforced from Clover output (`tests/cache/coverage/clover.xml`) via `tests/check-coverage-threshold.php`.
- Current statement coverage threshold is `35%` (raised from measured `51.58%` signal on 2026-03-08).
- Threshold ratcheting policy:
  - Raise only after at least 3 consecutive green CI coverage runs and 1 local confirmation run.
  - Raise in small increments (normally 1-2 points).
  - Do not lower threshold for feature work. If rollback is required (environment drift or confirmed flake), limit to 1 point and document follow-up.

Static analysis baseline policy
-------------------------------

- `composer test:phpstan` is a blocking quality gate (level 5 with `phpstan-baseline.neon`).
- `composer test:psalm` is currently advisory/non-blocking in CI phase 1 and uses `psalm-baseline.xml`.
- New work should avoid adding fresh baseline entries; reduce baseline counts when touching related files.

Local development smoke testing
-------------------------------

- Use `composer test:local-smoke` for deterministic local validation against `single-site-local.local`.
- Use `bin/wp-local-single-site.sh` for WP-CLI commands in Local where default DB host routing fails.
- Full local checklist: `docs/manual-testing-checklist.md`.

Current CI baseline
-------------------

- Treat failing run `22812182249` as historical (pre-fix).
- Use successful run `22812774619` on `develop` as the current baseline for Quality workflow health.

Fork-first workflow
-------------------

1. Default integration branch is `develop` on `dknauss/comment-popularity`.
2. Keep upstream PRs to a minimum; ship from the fork unless explicitly needed.
3. Use short-lived feature branches only when needed, then delete local and remote branches after merge.
4. Follow the branch/checklist details in `FORK_FIRST_POLICY.md`.

Contributions
-------------

Pull requests, reporting issues, feedback and ideas for new features and improvements are always welcome!

Releasing a new version
-----------------------

WordPress.org distribution is not part of this project workflow right now. The plugin is closed on WordPress.org (closed on March 3, 2021), so do not use SVN deploy tooling for releases.

Release from the fork instead:

1. Merge release-ready changes to `develop`.
2. Bump version metadata (`comment-popularity.php`, `README.txt`, and changelog files).
3. Run the quality baseline commands in this document.
4. Merge `develop` to `master` through a PR with green required checks.
5. Create and push a Git tag, then publish a GitHub Release.

Available Grunt tasks
---------------------

Linting: `grunt lint`
Minifying JS: `grunt minify`
Minify CSS: `cssmin`

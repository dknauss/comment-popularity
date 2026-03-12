Unreleased
==========

- Target version: 1.5.3-dev.
- Compat: aligned plugin metadata, Composer requirement, and runtime guard to PHP `8.2+`.
- Tooling: `bin/php-runtime.sh` now enforces PHP `8.2+` and falls back to Local PHP `8.4`/`8.3`/`8.2` when host `php` is older.
- Docs: removed stale `--ignore-platform-reqs` guidance and documented PHP `8.2+` as the baseline.
- Tests: added uninstall regression coverage for option/meta cleanup, comment karma reset, and capability teardown.
- Tests: added multisite uninstall regression coverage for blog-scoped option cleanup across network sites.
- Tests: added direct `comment_vote_callback()` regression coverage for nonce failure, invalid comment/action, missing visitor, guest-voting-disabled, and success-path responses.
- Tests: added guest visitor persistence coverage for single-site and multisite storage behavior.
- Tests: added experts widget regression coverage for empty expert result sets and HTTPS Gravatar URL generation.
- Fix: `HMN_Comment_Popularity::deactivate()` now removes custom capabilities using `has_cap()` checks.
- Fix: uninstall cleanup now targets the runtime comments table (`$wpdb->comments`) and executes capability cleanup via `deactivate()`.
- Fix: uninstall now removes blog-scoped plugin options (`comment_popularity_prefs`, `hmn_cp_plugin_version`, `hmn_cp_guests_logged_votes`) across all sites on multisite.
- Fix: experts widget now initializes an empty return array in `get_experts()` and uses `https://gravatar.com` avatar URLs.
- Fix: retired the unused `hmn_cp_interval` throttling hook; server-side vote integrity is now explicitly state-based (duplicate vote rejection plus transition normalization).
- CI: added `composer test:integration` baseline command (aliases current PHPUnit integration suite).
- CI: raised coverage gate threshold from `29%` to `35%` after increasing measured statement coverage to `51.58%` (`474/919`).
- Tests: added upgrade routine regression coverage for new install, legacy option migration, and current-version no-op paths.
- CI: raised coverage gate threshold from `23%` to `25%` after increasing measured statement coverage to `26.37%`.
- CI: added blocking `phpstan` quality check with committed level-5 baseline (`phpstan-baseline.neon`).
- CI: added phase-1 non-blocking `psalm` quality check with committed baseline (`psalm-baseline.xml`).
- Tooling: added Composer scripts for `analyse:phpstan`, `analyse:psalm`, `test:phpstan`, and `test:psalm`.
- Docs: updated contributing and fork-first quality baselines to include static analysis gates.
- Tests: added admin settings/profile regression tests and helper regression tests.
- Tests: added bootstrap init regression tests for logged-in and guest-voting-disabled paths (`hmn_cp_init`).
- Fix: author karma attribution now resolves by registered comment `user_id` only in both vote processing and `hmn_cp_the_comment_author_karma()` output (guest-email collisions no longer mutate registered user karma).
- CI: raised coverage gate threshold from `25%` to `27%` after increasing measured statement coverage to `43.68%` (`401/918`).
- CI: raised coverage gate threshold from `27%` to `29%` after increasing measured statement coverage to `44.99%` (`413/918`).
- Static analysis: reduced PHPStan baseline entries and updated Psalm baseline after helper-path cleanup.
- Tooling: added `bin/php-runtime.sh` and routed Composer quality commands through it to prefer a compatible local PHP runtime.

1.5.1 - 2026-03-08
==================

- Bug fix: member vote state now persists an empty array when the last logged vote is removed.
- Bug fix: repeated direct vote requests no longer desynchronize legacy karma from Wilson vote metadata.
- Tests: visitor persistence and Wilson vote transition coverage expanded.
- CI: quality workflow now uses deterministic Composer scripts and pinned WordPress test versions.
- CI: PHPCS changed-files and full-repository checks now run clean under the updated baseline ruleset.
- CI: WordPress test bootstrap no longer depends on `svn`; tests are fetched from `wordpress-develop` archives.
- Compat: `get_comments_sorted_by_weight()` accepts both legacy and modern parameter order.
- Docs: fork-first workflow policy is now codified as canonical process.

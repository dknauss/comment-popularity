Unreleased
==========

- Target version: 1.5.2-dev.
- Tests: added uninstall regression coverage for option/meta cleanup, comment karma reset, and capability teardown.
- Fix: `HMN_Comment_Popularity::deactivate()` now removes custom capabilities using `has_cap()` checks.
- Fix: uninstall cleanup now targets the runtime comments table (`$wpdb->comments`) and executes capability cleanup via `deactivate()`.
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
- Fix: `hmn_cp_the_comment_author_karma()` now resolves commenter karma by registered comment `user_id` instead of email lookup.
- CI: raised coverage gate threshold from `25%` to `27%` after increasing measured statement coverage to `43.68%` (`401/918`).
- CI: raised coverage gate threshold from `27%` to `29%` after increasing measured statement coverage to `44.99%` (`413/918`).
- Static analysis: reduced PHPStan baseline entries and updated Psalm baseline after helper-path cleanup.
- Tooling: added `bin/php-runtime.sh` and routed Composer quality commands through it to prefer a compatible local PHP runtime on PHP 8.5+ hosts.

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

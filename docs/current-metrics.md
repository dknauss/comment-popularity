# Current Metrics (Canonical)

This file is the single source of truth for current repository counts.

Last verified: 2026-03-09
Verification environment: local repo checkout at `/Users/danknauss/Documents/GitHub/comment-popularity`

## Test Metrics

| Metric | Value | Verification |
|---|---:|---|
| PHPUnit tests | 57 tests | `composer test` |
| PHPUnit assertions | 129 assertions | `composer test` |
| Test files | 11 | `find ./tests -name "test-*.php" \| wc -l` |
| Coverage threshold | 35% | `tests/check-coverage-threshold.php` |

## Size Metrics

| Metric | Value | Verification |
|---|---:|---|
| Production PHP lines (`inc/`, `admin/`, entry, uninstall — excluding `inc/lib/`) | 2,411 | `find ./inc ./admin -type f -name "*.php" -not -path "*/lib/*" -not -path "*/templates/*" -print0 \| xargs -0 wc -l \| tail -1` + `wc -l comment-popularity.php uninstall.php` |
| Test PHP lines (`tests/`) | 1,938 | `find ./tests -type f -name "*.php" -not -path "*/cache/*" -print0 \| xargs -0 wc -l \| tail -1` |
| Test-to-production ratio | 0.80:1 | `1938 / 2411` |

## Architectural Facts

Volatile counts that change when features ship. Every doc referencing these
numbers MUST point to or be verified against this table.

| Fact | Value | Verification | Last changed |
|---|---:|---|---|
| Vote operations | 2 | upvote, downvote | v1.0 |
| Ranking algorithms | 2 | legacy karma, Wilson confidence | fork (Wilson added) |
| Visitor types | 2 | Member (user_id), Guest (IP) | v1.0 |
| Widget types | 1 | Experts widget | v1.0 |
| PHPStan level | 5 | `grep "level:" phpstan.neon.dist` | Phase 7 |
| Psalm baseline | committed | `psalm-baseline.xml` | Phase 7 |
| Twig version | 3.x | `composer show twig/twig \| grep versions` | Phase 7 |

### Files that reference these counts

- `README.md`, `README.txt` — plugin description
- `ROADMAP.md` — phase priorities and baselines
- `CHANGELOG_UNRELEASED.md` — in-progress release notes

## CI Matrix Snapshot

Source: `.github/workflows/quality.yml`

- PHP version: 8.2
- WordPress version: 6.4
- Jobs: phpcs-changed (blocking), phpcs-report (audit), phpstan (blocking), psalm (advisory), phpunit (blocking), coverage-gate (35% threshold, blocking)

## Verification Notes

- `composer test` passed on 2026-03-09 (57 tests, 129 assertions, 2 skipped).
- `composer analyse:phpstan` passed on 2026-03-09.
- `composer lint` passed on 2026-03-09.

## Update Procedure

1. Re-run all verification commands listed above.
2. Update this file first.
3. Keep other docs referencing this file instead of duplicating current counts.

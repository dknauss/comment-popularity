# Current Metrics (Canonical)

This file is the single source of truth for current repository counts.
All counts below are measured against the `develop` branch (canonical).

Last verified: 2026-03-12
Verification environment: local repo checkout at `/Users/danknauss/Documents/GitHub/comment-popularity`

## Test Metrics

| Metric | Value | Verification |
|---|---:|---|
| PHPUnit tests (develop) | 45 test methods | `grep -c "function test" tests/test-*.php` (sum) |
| PHPUnit tests (Phase 8 branch) | 57 tests, 129 assertions | `composer test` (requires WP test database) |
| Test files (develop, git-tracked) | 8 | `git ls-files 'tests/test-*.php' \| wc -l` |
| Coverage threshold | 35% | `tests/check-coverage-threshold.php` |

> **Note:** The PHPUnit test and assertion counts can only be verified by running
> `composer test`, which requires the WordPress test database (`/tmp/wordpress-tests-lib`).
> The 57/129 count was last verified on 2026-03-09 against the Phase 8 branch
> (`codex/testing-related-fixes-isolated`), which has additional test files.
> The `develop` baseline has 8 test files with 45 test methods.

## Size Metrics

| Metric | Value | Verification |
|---|---:|---|
| Production PHP lines (`inc/`, `admin/`, entry, uninstall — excluding `inc/lib/`, `inc/templates/`) | 2,406 | `find ./inc ./admin -type f -name "*.php" -not -path "*/lib/*" -not -path "*/templates/*" -print0 \| xargs -0 wc -l \| tail -1` + `wc -l comment-popularity.php uninstall.php` |
| Test PHP lines (`tests/`, excluding cache) | 1,402 | `find ./tests -type f -name "*.php" -not -path "*/cache/*" -print0 \| xargs -0 wc -l \| tail -1` |
| Test-to-production ratio | 0.58:1 | `1402 / 2406` |

## Architectural Facts

Volatile counts that change when features ship. Every doc referencing these
numbers MUST point to or be verified against this table.

| Fact | Value | Verification | Last changed |
|---|---:|---|---|
| Vote operations | 2 | upvote, downvote | v1.0 |
| Ranking algorithms | 2 | legacy karma, Wilson confidence | fork (Wilson added) |
| Visitor types | 2 | Member (user_id), Guest (IP) | v1.0 |
| Widget types | 2 | Most Voted, Experts | v1.0 |
| PHPStan level | 5 | `grep "level:" phpstan.neon.dist` | Phase 7 |
| Psalm baseline | committed | `psalm-baseline.xml` | Phase 7 |
| Twig version | 3.x | `composer show twig/twig \| grep versions` | Phase 7 |

### Files that reference these counts

- `README.md`, `README.txt` — plugin description
- `ROADMAP.md` — phase priorities and baselines
- `CHANGELOG_UNRELEASED.md` — in-progress release notes
- `CLAUDE.md`, `AGENTS.md` — agent guidance
- `.planning/STATE.md` — workflow state

## CI Matrix Snapshot

Source: `.github/workflows/quality.yml`

- PHP version: 8.2
- WordPress version: 6.4
- Jobs: phpcs-changed (blocking), phpcs-report (audit), phpstan (blocking), psalm (advisory), phpunit (blocking), coverage-gate (35% threshold, blocking)

## Verification Notes

- LOC counts verified on `develop` on 2026-03-12.
- `composer test` last passed on 2026-03-09 (57 tests, 129 assertions, 2 skipped) — on Phase 8 branch.
- `composer analyse:phpstan` passed on 2026-03-09.
- `composer lint` passed on 2026-03-09.

## Verification Script

Run after any structural edit:

```bash
cd /Users/danknauss/Documents/GitHub/comment-popularity

echo "=== Branch ==="
git branch --show-current

echo "=== Production PHP ==="
find ./inc ./admin -type f -name "*.php" -not -path "*/lib/*" -not -path "*/templates/*" -print0 | xargs -0 wc -l | tail -1
wc -l comment-popularity.php uninstall.php

echo "=== Test PHP ==="
find ./tests -type f -name "*.php" -not -path "*/cache/*" -print0 | xargs -0 wc -l | tail -1

echo "=== Test files ==="
git ls-files 'tests/test-*.php' | wc -l

echo "=== Architectural ==="
echo "PHPStan level: $(grep 'level:' phpstan.neon.dist)"
echo "Widget types: $(find ./inc/widgets -maxdepth 1 -name 'class-widget-*.php' | wc -l)"
```

## Update Procedure

1. Re-run the verification script above.
2. Compare results to this table. Update any changed values.
3. Update all files listed in "Files that reference these counts."
4. Update `CHANGELOG_UNRELEASED.md` with the change.

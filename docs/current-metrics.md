# Current Metrics (Canonical)

This file is the single source of truth for current repository counts.

Last verified: 2026-03-15
Verification environment: local repo checkout at `/Users/danknauss/Documents/GitHub/comment-popularity`

## Test Metrics

| Metric | Value | Verification |
|---|---:|---|
| PHPUnit tests | 63 tests | `composer test` |
| PHPUnit assertions | 146 assertions | `composer test` |
| Test files | 10 | `find ./tests -name "test-*.php" \| wc -l` |
| Coverage threshold | 35% | `tests/check-coverage-threshold.php` |

## Size Metrics

| Metric | Value | Verification |
|---|---:|---|
| Production PHP lines (`inc/`, `admin/`, entry, uninstall — excluding `inc/lib/`) | 2,416 | `find ./inc ./admin -type f -name "*.php" -not -path "*/lib/*" -not -path "*/templates/*" -print0 \| xargs -0 wc -l \| tail -1` + `wc -l comment-popularity.php uninstall.php` |
| Test PHP lines (`tests/`) | 2,032 | `find ./tests -type f -name "*.php" -not -path "*/cache/*" -print0 \| xargs -0 wc -l \| tail -1` |
| Test-to-production ratio | 0.84:1 | `2032 / 2416` |

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

- LOC and test file counts verified on `codex/phase-07-03-autoload` on 2026-03-15.
- `composer test` passed on 2026-03-15 (63 tests, 146 assertions, 2 skipped).
- `composer analyse:phpstan` passed on 2026-03-15.
- `composer analyse:psalm` passed on 2026-03-15.
- `composer lint` passed on 2026-03-15.

## Verification Script

Run after any structural edit:

```bash
cd /Users/danknauss/Documents/GitHub/comment-popularity

echo "=== Production PHP ==="
find ./inc ./admin -type f -name "*.php" -not -path "*/lib/*" -not -path "*/templates/*" -print0 | xargs -0 wc -l | tail -1
wc -l comment-popularity.php uninstall.php

echo "=== Test PHP ==="
find ./tests -type f -name "*.php" -not -path "*/cache/*" -print0 | xargs -0 wc -l | tail -1

echo "=== Test files ==="
find ./tests -name "test-*.php" | wc -l

echo "=== Architectural ==="
echo "PHPStan level: $(grep 'level:' phpstan.neon.dist)"
echo "Widget types: $(find ./inc/widgets -name 'class-widget-*.php' | wc -l)"
```

## Update Procedure

1. Re-run the verification script above.
2. Compare results to this table. Update any changed values.
3. Update all files listed in "Files that reference these counts."
4. Update `CHANGELOG_UNRELEASED.md` with the change.

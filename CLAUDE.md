# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Comment Popularity is a WordPress plugin that adds upvote/downvote controls for comments, tracks commenter karma, and supports ranking by either legacy karma or Wilson confidence-based scoring. This is an active fork (`dknauss/comment-popularity`) from the original Human Made project.

**Requirements:** WordPress 6.4+, PHP 8.2+

## Commands

```bash
composer install              # Install dependencies (vendor lives in inc/lib/)
composer test                 # Run PHPUnit integration tests
composer test:setup           # Install WordPress test suite (requires WP_VERSION env var)
composer test:coverage        # Run tests with PCOV coverage (35% threshold gate)
composer test:phpstan         # Run PHPStan level 5 (with committed baseline)
composer test:psalm           # Run Psalm (with committed baseline, advisory)
composer lint                 # Run PHPCS (WordPress-Extra standard)
composer lint:full-report     # PHPCS with summary report
composer qa                   # Lint + test combined
composer test:local-smoke     # Local smoke tests (targets https://single-site-local.local)
```

All commands use `bin/php-runtime.sh` which cascades to Local PHP 8.4/8.3/8.2 when available.

## Documentation

- `ROADMAP.md` — fork priorities, phase status, guardrails.
- `FORK_FIRST_POLICY.md` — fork governance and upstream submission rules.
- `CONTRIBUTING.md` — contributor setup and workflow.
- `CHANGELOG_UNRELEASED.md` — in-progress release notes.
- `docs/manual-testing-checklist.md` — UI/UX testing prompts.

## Verification Requirements

### Internal architectural counts

- **MUST** check `docs/current-metrics.md` before writing any count that appears
  there (tests, LOC, PHPStan level, coverage threshold).
- When adding a feature that changes a count, update `current-metrics.md`
  FIRST, then update all files listed in its "Files that reference these
  counts" section.

### Pre-release audit

Update the test counts in `README.md` and `CHANGELOG_UNRELEASED.md` if they
changed since the last release. Use `composer test` output as the source.

## Architecture

**Entry point:** `comment-popularity.php` — PHP version check, requires main class, registers activation/deactivation hooks, initializes visitor (Member or Guest based on login state).

**Namespace:** `CommentPopularity\`

### Core Classes (in `inc/`)

- **HMN_Comment_Popularity** — Singleton. Registers hooks for comment display, vote handling, karma tracking, comment sorting. Manages vote AJAX callback (`comment_vote_callback()`).
- **HMN_CP_Visitor** (abstract) — Base class for vote tracking. Two concrete implementations:
  - **HMN_CP_Visitor_Member** — Tracks votes in user meta (`hmn_cp_votes`).
  - **HMN_CP_Visitor_Guest** — Tracks votes in a site option (`hmn_cp_guests_logged_votes`), keyed by IP.
- **helpers.php** — Utility functions: `get_comment_author_karma()`, Wilson confidence calculation, Gravatar URL generation.
- **upgrade.php** — Version migration runner.

### Key Behaviors

- **Vote storage:** Comment meta `hmn_cp_upvote_count` / `hmn_cp_downvote_count` per comment. Author karma in user meta `hmn_cp_karma`.
- **Sorting:** `comment_popularity_sort_comments_by_weight` filter hooks into `comments_clauses` to sort by karma or Wilson score.
- **Guest voting:** Controlled by `hmn_cp_allow_guest_voting` filter (default: disabled).
- **Templates:** Twig 3.x templates in `inc/templates/`.
- **Vendor:** Composer vendor directory is `inc/lib/` (non-standard, inherited from upstream).

### Admin

- **HMN_Comment_Popularity_Admin** — Settings page under Settings > Comment Popularity. Manages display preferences, default sort order.

### Widget

- **Widget_Experts** — Shows top commenters by karma with Gravatar images.

## Testing

Integration tests in `tests/` use WordPress test suite (`WP_UnitTestCase`). Requires one-time setup: `composer test:setup` with `WP_VERSION` env var, or `bash bin/install-wp-tests.sh`.

Test files follow the `test-*.php` naming convention.

PHPUnit strict mode is not enabled (inherited upstream config).

## Commit Practices

- Use conventional commit format.
- Fork-first workflow: `develop` is the canonical branch.
- Follow Red-Green-Refactor for all behavior changes in Phase 8+.
- `composer qa` (lint + test) should pass before every commit.

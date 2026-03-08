# Comment Popularity (Fork)

Comment Popularity adds upvote/downvote controls for WordPress comments, tracks commenter karma, and supports ranking by either legacy karma or Wilson score.

This repository is the active fork (`dknauss/comment-popularity`) and is operated with a fork-first workflow.

## Project Status

- Canonical integration branch: `develop`
- Long-lived release branch: `master`
- Active release channel: Git tags + GitHub Releases from this fork
- WordPress.org plugin distribution: closed (March 3, 2021)
- Upstream PRs (`humanmade/comment-popularity`): minimized and optional

For canonical branch/release policy, see [FORK_FIRST_POLICY.md](FORK_FIRST_POLICY.md).

## What Changed In This Fork

- Deterministic test bootstrap (`bin/test-setup.sh`) and stable CI run order
- Enforced coverage gate (`composer test:coverage`) with current threshold at **27%**
- Blocking PHPStan gate with committed baseline (`phpstan-baseline.neon`)
- Phase-1 advisory Psalm analysis with committed baseline (`psalm-baseline.xml`)
- Local smoke testing for Local site environment (`single-site-local.local`)

## Plugin Behavior

Core behavior:

- Logged-in users can upvote/downvote comments
- Vote transitions (`upvote -> downvote`, `downvote -> upvote`, `undo`) are tracked consistently
- Commenters accrue karma from votes on their comments
- Admins can mark users as experts and set default expert karma
- Comment ordering supports:
  - `karma` (legacy)
  - `wilson` (confidence-based ranking)

Key filters:

- `hmn_cp_allow_guest_voting` to allow guest voting
- `hmn_cp_allow_negative_comment_weight` to allow negative comment weight
- `hmn_cp_sort_comments_by_weight` to disable custom sorting

Example (mu-plugin or theme/plugin bootstrap):

```php
add_filter( 'hmn_cp_allow_guest_voting', '__return_true' );
add_filter( 'hmn_cp_allow_negative_comment_weight', '__return_true' );
```

## Install (Manual)

1. Clone or copy this plugin to `wp-content/plugins/comment-popularity`.
2. Activate **Comment Popularity** in wp-admin.
3. Configure defaults under **Settings -> Discussion**.

## Development Quick Start

From repo root:

```bash
composer install --no-interaction --prefer-dist --ignore-platform-reqs
WP_VERSION=6.4 composer test:setup
composer lint
composer test
composer test:coverage
composer test:phpstan
composer test:psalm
composer test:local-smoke
```

Notes:

- `--ignore-platform-reqs` is currently required because locked legacy Twig constraints predate modern local runtimes.
- Coverage artifacts are written to `tests/cache/coverage`.
- `composer test:local-smoke` targets `https://single-site-local.local`.

## CI Quality Gates

Required checks on `develop`:

- `phpcs-changed`
- `phpcs-report`
- `phpstan`
- `phpunit (8.1, 6.4)`
- `coverage-gate`

Advisory check:

- `psalm` (phase 1, non-blocking)

## Release Process (Fork)

1. Land release-ready work on `develop` with green required checks.
2. Bump version metadata and changelog files.
3. Merge `develop` into `master` via PR.
4. Create and push release tag.
5. Publish GitHub Release from that tag.

Do not use WordPress.org SVN deploy flow for this repository.

## Documentation Map

- Workflow policy: [FORK_FIRST_POLICY.md](FORK_FIRST_POLICY.md)
- Contributor workflow and commands: [CONTRIBUTING.md](CONTRIBUTING.md)
- Local manual smoke checklist: [docs/manual-testing-checklist.md](docs/manual-testing-checklist.md)
- Current release roadmap: [ROADMAP.md](ROADMAP.md)
- In-progress release notes: [CHANGELOG_UNRELEASED.md](CHANGELOG_UNRELEASED.md)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).

# Fork-First Development Policy

## Purpose

Keep delivery velocity and quality control in the fork (`dknauss/comment-popularity`) without relying on upstream approvals.

## Canonical Integration Branch

- The canonical integration branch is `develop` on `dknauss/comment-popularity`.
- Ship-ready code must land on fork `develop` with passing required checks.

## Pull Request Policy

- Upstream PRs (`humanmade/comment-popularity`) are optional and minimized.
- Open upstream PRs only when there is a clear external need.
- Do not keep overlapping upstream PR stacks open.

## Branch Lifecycle

- Prefer direct work on `develop` for small changes.
- Use short-lived feature branches only when needed for risky or parallel work.
- Delete feature branches (local and remote) immediately after merge.
- Prune stale worktrees and remote-tracking refs routinely.

## Quality Gates

- Required checks on `develop`:
  - `phpcs-changed`
  - `phpcs-report`
  - `phpunit (8.1, 6.4)`
- Treat failing run `22812182249` as historical pre-fix noise.
- Use run `22812774619` and newer successful `develop` runs as baseline.

## Test Bootstrap Standard

- WordPress test suite setup must not depend on `svn`.
- `bin/install-wp-tests.sh` uses `wordpress-develop` archives so CI works on standard GitHub runners without extra package installs.

## Working Checklist

1. Start from fork `develop`.
2. Run `composer lint:full-report`.
3. Run `WP_VERSION=6.4 composer test:setup` and `composer test`.
4. Push to fork `develop`.
5. Verify `Quality` workflow passes.
6. Prune temporary branches/worktrees.

# 08-02 Summary

Status: Complete

Coverage execution model established:
- `composer test:setup` installs the WordPress test bootstrap in `/tmp/wordpress-tests-lib`.
- `composer test:integration` is the canonical integration suite for both single-site and multisite execution.
- `composer test:coverage` is the canonical coverage run and writes clover output to [tests/cache/coverage/clover.xml](/Users/danknauss/Documents/GitHub/comment-popularity/tests/cache/coverage/clover.xml).
- The enforced coverage threshold is 35%, and roadmap/state docs should be updated whenever that threshold changes.

Blocking coverage gaps closed during Phase 8:
- `comment_vote_callback()` contract coverage
- guest persistence coverage
- multisite uninstall coverage
- experts widget helper coverage

Remaining posture:
- public voting/lifecycle risks are covered
- future coverage work is incremental hardening, not a blocker for the current fork baseline

Verification reference:
- `composer lint`
- `composer analyse:phpstan`
- `composer analyse:psalm`
- `WP_VERSION=6.4 composer test:integration`
- `WP_VERSION=6.4 WP_MULTISITE=1 composer test:integration`
- `composer test:coverage`

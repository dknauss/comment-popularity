# 08-01 Summary

Status: Complete

Delivered outcomes:
- Author karma attribution now follows registered comment `user_id` only.
- Uninstall cleanup is multisite-safe for blog-scoped plugin state.
- The dead `hmn_cp_interval` throttling path is retired in favor of explicit server-side transition rules.
- Public vote callback coverage and guest persistence coverage are landed.
- Experts widget defects and release/planning doc contradictions are resolved.

Key files:
- [inc/class-comment-popularity.php](/Users/danknauss/Documents/GitHub/comment-popularity/inc/class-comment-popularity.php)
- [inc/class-visitor.php](/Users/danknauss/Documents/GitHub/comment-popularity/inc/class-visitor.php)
- [uninstall.php](/Users/danknauss/Documents/GitHub/comment-popularity/uninstall.php)
- [tests/test-comment-vote-callback.php](/Users/danknauss/Documents/GitHub/comment-popularity/tests/test-comment-vote-callback.php)
- [tests/test-visitor.php](/Users/danknauss/Documents/GitHub/comment-popularity/tests/test-visitor.php)
- [tests/test-uninstall.php](/Users/danknauss/Documents/GitHub/comment-popularity/tests/test-uninstall.php)

Verification reference:
- `composer lint`
- `composer analyse:phpstan`
- `composer analyse:psalm`
- `WP_VERSION=6.4 composer test:integration`
- `WP_VERSION=6.4 WP_MULTISITE=1 composer test:integration`

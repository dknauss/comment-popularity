## Summary

Fork-first note: this repository ships from the fork. Use this PR body only when there is an explicit decision to export this branch upstream.

This PR hardens the core voting flow and fixes vote-state persistence regressions that can desynchronize stored visitor state, comment karma, and author karma.

## What Changed

- Normalize vote transitions server-side for repeat, undo, and switch-vote requests.
- Persist an empty member vote state when the last logged vote is removed.
- Preserve guest vote storage on single-site installs instead of overwriting all prior guest state.
- Guard invalid AJAX requests when `comment_id` is invalid or no visitor object exists.
- Sanitize vote/profile input handling and add a nonce check for user-meta updates.
- Add regression coverage for vote transitions and visitor persistence.

## Why This Is Separate

This is the correctness baseline for the rest of the modernization queue. The later sort, WPCS, CI, and Wilson work should not land before these server-side behaviors are stable.

## Testing

- `php -l` on the changed PHP files
- Added regression coverage in `tests/test-comment-popularity.php`
- Added regression coverage in `tests/test-visitor.php`
- Full PHPUnit execution is expected to run in CI once the WordPress test bootstrap is available

## Supersedes

- Replaces closed, unmerged `#132`

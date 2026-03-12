## Summary

Fork-first note: this repository ships from the fork. Use this PR body only when there is an explicit decision to export this branch upstream.

This draft PR adds Wilson-score comment ranking groundwork, an admin-controlled ranking-mode setting, and the test coverage needed to keep Wilson metadata aligned with legacy comment karma behavior.

## Status

This PR should stay in draft until the lower restacked queue has landed:

- core correctness
- sort-signature compatibility
- WPCS modernization
- CI quality checks

## What Changed

- Add Wilson vote metadata storage and Wilson lower-bound calculation.
- Allow comment sorting by Wilson score when Wilson mode is active.
- Add a discussion setting to switch ranking mode between legacy karma and Wilson score.
- Extend admin settings validation for the new ranking mode.
- Add Wilson-focused regression coverage in `tests/test-wilson-ranking.php`.
- Keep the corrected vote-transition logic so repeated direct votes do not desynchronize legacy karma from Wilson counters.

## Why This Is Separate

Wilson work changes product behavior and sorting semantics. It should be reviewed only after the correctness and tooling baseline is stable.

## Testing

- `php -l admin/class-comment-popularity-admin.php`
- `php -l inc/class-comment-popularity.php`
- `php -l tests/test-comment-popularity.php`
- `php -l tests/test-wilson-ranking.php`
- Full PHPUnit execution is expected in CI after the lower queue lands

## Supersedes

- Supersedes open `#136`
- Absorbs the closed, unmerged Wilson groundwork from `#134`

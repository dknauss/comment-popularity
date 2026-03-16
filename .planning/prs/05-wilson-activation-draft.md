## Summary

Fork-first note: this repository ships from the fork. Use this PR body only when there is an explicit decision to export this branch upstream.

Historical note: this draft no longer matches the live fork. Wilson-score ranking, ranking-mode settings, Wilson metadata storage, and related regression coverage are already implemented in the current codebase.

## Status

Obsolete as an execution artifact. Retain only as a historical record of the older upstream export queue.

## What Changed

- Historical scope only:
- Wilson vote metadata storage and Wilson lower-bound calculation.
- Comment sorting by Wilson score when Wilson mode is active.
- Discussion setting to switch ranking mode between legacy karma and Wilson score.
- Admin settings validation for the ranking mode.
- Wilson-focused regression coverage in `tests/test-wilson-ranking.php`.
- Vote-transition logic that keeps legacy karma aligned with Wilson counters.

## Why This Is Separate

This draft originally isolated Wilson-related product behavior from the lower correctness/tooling queue. That separation is no longer actionable because the fork has already absorbed this work.

## Testing

Current verification for Wilson behavior lives in the main repo test suite, especially `tests/test-wilson-ranking.php`, not in this historical draft.

## Supersedes

- Supersedes open `#136`
- Absorbs the closed, unmerged Wilson groundwork from `#134`

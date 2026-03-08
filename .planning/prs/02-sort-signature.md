## Summary

Fork-first note: this repository ships from the fork. Use this PR body only when there is an explicit decision to export this branch upstream.

This PR fixes the deprecated `get_comments_sorted_by_weight()` method signature and updates the internal call sites to match the corrected parameter order.

## What Changed

- Change `get_comments_sorted_by_weight()` to accept `array $args` before the optional `$html` parameter.
- Update helper, template, and widget call sites to use the corrected signature.

## Why This Is Separate

This is a small compatibility fix with a clean review surface. Keeping it separate from the correctness and WPCS branches makes it easier to land quickly.

## Testing

- `php -l inc/class-comment-popularity.php`
- `php -l inc/helpers.php`
- `php -l inc/templates/comments.php`
- `php -l inc/widgets/class-widget-most-voted.php`

## Supersedes

- Supersedes open `#135`

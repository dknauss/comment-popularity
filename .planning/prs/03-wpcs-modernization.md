## Summary

This PR modernizes a few legacy helper patterns and adds a project-local PHPCS ruleset so linting can run against the plugin's actual conventions instead of a generic standard invocation.

## What Changed

- Add `phpcs.xml.dist` for the plugin.
- Update Composer dev dependencies for the WPCS 3.x toolchain.
- Use `wp_parse_url()` for cookie scheme checks.
- Replace legacy `strip_tags()` calls with `wp_strip_all_tags()`.
- Fix the experts widget admin include path.
- Keep the previously corrected sort-signature call pattern intact.

## Why This Is Separate

This is repo-level modernization and linting prep. It is safer to review after the core correctness fixes are landed and before CI starts enforcing the ruleset.

## Testing

- `php -l inc/class-visitor.php`
- `php -l inc/widgets/class-widget-most-voted.php`
- `php -l inc/widgets/experts/class-widget-experts.php`
- PHPCS is intended to validate this ruleset once the CI branch lands

## Supersedes

- Supersedes open `#133`

# Comment Popularity Manual Testing Checklist

Last updated: 2026-03-08 (America/Edmonton)

## Purpose

Use this checklist to validate Comment Popularity behavior in a real WordPress site before release promotion.

This is the local hardening companion to automated CI gates.

## Environment and prerequisites

Before running tests:

- [ ] Local site `single-site-local.local` is running.
- [ ] Plugin directory is symlinked/mounted into local `wp-content/plugins`.
- [ ] `comment-popularity` plugin is active.
- [ ] You can run WP-CLI from this repository root.

Recommended environment variable:

```bash
export WP_URL="https://single-site-local.local"
```

## Automated local smoke gate

Run:

```bash
composer test:local-smoke
```

Expected:

- Site responds at `${WP_URL}/wp-json/`.
- Plugin is active in local site.
- Deterministic vote flow (`upvote -> undo`) passes and restores weight to `0`.

## UI checks (wp-admin + frontend)

### [ ] UI-01 Logged-in voting controls render

Steps:
1. Sign in as an administrator.
2. Open a post with comments on the frontend.
3. Confirm upvote/downvote controls render beside each comment.

Expected:
- Voting controls are visible.
- No JS errors or PHP warnings are displayed.

### [ ] UI-02 Upvote, downvote, and undo behavior

Steps:
1. Upvote a comment.
2. Undo the same vote.
3. Downvote the same comment.

Expected:
- Weight changes exactly once per action.
- Undo returns weight to expected prior state.
- No duplicate increments/decrements occur.

### [ ] UI-03 Guest voting toggle behavior

Steps:
1. Log out.
2. Confirm voting controls are hidden by default.
3. Enable guest voting via filter in local mu-plugin:
   - `add_filter( 'hmn_cp_allow_guest_voting', '__return_true' );`
4. Reload frontend and attempt voting as guest.

Expected:
- Default state blocks guest voting.
- Filter-enabled state allows guest voting without fatal errors.

### [ ] UI-04 Ranking mode settings behavior

Steps:
1. In `Settings -> Discussion`, set ranking mode to `Wilson score lower bound`.
2. Save settings and reload a post with several comments/votes.
3. Switch back to `Karma (current behavior)`.

Expected:
- Setting persists and applies comment ordering.
- Switching modes does not produce warnings/fatals.

## WP-CLI hardening checks

Use local wrapper command:

```bash
bin/wp-local-single-site.sh <wp-command>
```

### [ ] CLI-01 Plugin status and version

Run:

```bash
bin/wp-local-single-site.sh plugin list --fields=name,status,version --format=table
```

Expected:
- `comment-popularity` is `active`.
- Version matches intended branch state (`develop` cycle or release branch).

### [ ] CLI-02 Invalid ranking mode fallback

Run:

```bash
bin/wp-local-single-site.sh option patch update comment_popularity_prefs ranking_mode nonsense
bin/wp-local-single-site.sh eval 'echo CommentPopularity\HMN_Comment_Popularity::get_instance()->get_comment_ranking_mode();'
```

Expected:
- Printed mode is `karma` (invalid values are normalized).

### [ ] CLI-03 Coverage of vote transition invariants

Run:

```bash
composer test:local-smoke
```

Expected:
- Vote transition checks pass (`upvote -> undo`, final weight `0`).

## Release hardening gate

Before promoting to release branch/tag, all commands below should be green:

```bash
composer lint:full-report
composer test
composer test:coverage
composer test:local-smoke
```

## Notes

- `bin/wp-local-single-site.sh` auto-discovers Local site metadata for `single-site-local.local`.
- The wrapper enforces DB socket routing for WP-CLI in Local environments where default DB host resolution fails.

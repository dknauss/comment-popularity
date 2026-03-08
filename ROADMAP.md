# Comment Popularity Modernization Roadmap

## Purpose

Define an upstream-friendly backlog for modernizing Comment Popularity without turning it into a fork-only rewrite. The goal is to send small, reviewable improvements back to `humanmade/comment-popularity` while staying aligned with the existing plugin structure and WordPress coding standards.

## Workflow Direction (Current)

The active workflow for this repository is fork-first. `dknauss/comment-popularity` `develop` is the canonical integration branch, and upstream PRs are minimized. See `FORK_FIRST_POLICY.md` for the authoritative working process.
WordPress.org distribution is currently closed for this plugin (closed on March 3, 2021), so roadmap execution does not include SVN/.org release steps.

## Guardrails

- Follow the WordPress Coding Standards already called out in `CONTRIBUTING.md`.
- Prefer small PRs that change one concern at a time.
- Preserve existing public behavior, hooks, options, and template helpers unless a bug fix requires a narrowly scoped change.
- Avoid new runtime dependencies unless they are required for upstream maintenance.
- Use the repo's existing tooling where possible: Composer, PHPUnit, Grunt, and `bin/install-wp-tests.sh`.

## Current Baseline

- This repository is a traditional WordPress plugin, not a block plugin or modern JS app.
- The most urgent problems are correctness and data integrity, not feature expansion.
- The current fork checkout does implement Wilson scoring, ranking-mode settings, and a GitHub Actions quality workflow, so roadmap items should treat those as existing fork behavior.
- Runtime support declarations are still inconsistent: the plugin bootstrap and class constant still advertise PHP `5.3.2`, `composer.json` does not declare a PHP requirement, and fork docs/plans discuss higher floors that are not yet enforced in code.
- The PHPUnit suite depends on the WordPress test library bootstrap in `/tmp/wordpress-tests-lib`, which must be installed before tests can run.

## Phase 1: Correctness And Data Integrity

Ship the smallest bug fixes that protect stored vote state and prevent user-facing fatals.

### Backlog

1. Persist empty member vote state when the final logged vote is removed.
2. Preserve all guest vote records on single-site installs instead of overwriting the entire option with the current visitor's data.
3. Make guest vote retrieval null-safe so first-time visitors do not trigger warnings in rendering or voting flows.
4. Guard the AJAX callback when no visitor object is initialized and return a structured error instead of a fatal.
5. Validate that `comment_id` resolves to a real comment before processing a vote.
6. Use `comment->user_id` for author karma updates when available, instead of relying on comment author email alone.

### Acceptance Criteria

- No fatal error when an unauthenticated request hits the vote endpoint while guest voting is disabled.
- Guest voting does not erase other guests' recorded votes.
- Undoing a user's last vote leaves stored state consistent.
- Added regression tests cover each fixed bug.

### Likely File Scope

- `inc/class-comment-popularity.php`
- `inc/class-visitor.php`
- `tests/test-comment-popularity.php`
- `tests/test-visitor.php`

## Phase 2: Server-Side Voting Rules

Move vote integrity checks out of the browser and into the backend so the plugin behaves correctly under direct POSTs and repeated requests.

### Backlog

1. Enforce duplicate-vote and vote-interval rules on the server.
2. Normalize vote transitions so `upvote`, `downvote`, and `undo` update comment weight and author karma exactly once per state change.
3. Return stable error codes and payloads for invalid transitions.
4. Align guest and member validation paths where behavior should match.

### Acceptance Criteria

- Repeated identical votes do not keep increasing or decreasing karma.
- `undo` reverses the previous vote cleanly.
- The existing client script remains compatible with the response contract.
- Tests cover the full transition matrix: none to upvote, none to downvote, upvote to undo, downvote to undo, upvote to downvote, downvote to upvote.

### Likely File Scope

- `inc/class-comment-popularity.php`
- `inc/class-visitor.php`
- `js/voting.js`
- `tests/test-comment-popularity.php`

## Phase 3: PHP Compatibility And Standards Cleanup

Reduce avoidable warnings, deprecations, and standards drift without changing the plugin's architecture.

### Backlog

1. Reconcile the declared PHP support floor across `comment-popularity.php`, `HMN_CP_REQUIRED_PHP_VERSION`, `composer.json`, CI, and release docs before raising the minimum.
2. Fix PHP 8+ deprecations such as optional parameters preceding required parameters.
3. Sanitize and unslash request data consistently in AJAX and admin save handlers.
4. Clean up rendering issues such as misused escaping helpers and null-unsafe array access.
5. Fix the experts widget admin include path.
6. Switch Gravatar URLs to HTTPS.
7. Make uninstall routines prefix-safe and complete capability cleanup.

### Acceptance Criteria

- Version declarations, Composer constraints, CI targets, and release notes agree on the supported PHP floor.
- Core PHP files lint cleanly on a current PHP runtime without plugin-specific deprecation notices.
- PHPCS findings move toward a clean WordPress-standard baseline.
- Uninstall does not hardcode the comments table name.

### Likely File Scope

- `comment-popularity.php`
- `admin/class-comment-popularity-admin.php`
- `composer.json`
- `inc/class-comment-popularity.php`
- `inc/helpers.php`
- `inc/widgets/experts/class-widget-experts.php`
- `.github/workflows/*`
- `README.md`
- `CONTRIBUTING.md`
- `CHANGELOG_UNRELEASED.md`
- `uninstall.php`

## Phase 4: Test Foundation

Make the existing test suite credible before expanding the plugin's scope.

### Backlog

1. Fill `tests/test-visitor.php` with member and guest persistence tests.
2. Add AJAX regression tests for nonce failure, missing visitor, invalid comment ID, and invalid vote type.
3. Add vote transition tests for duplicate votes and undo behavior.
4. Document the local WordPress test bootstrap workflow in the repo.
5. Tighten PHPUnit configuration once the suite is stable enough to run cleanly.

### Acceptance Criteria

- The repo documents how to install the WordPress test library and run PHPUnit locally.
- The main bug fixes in Phases 1 and 2 are covered by automated tests.
- Contributors can reproduce the test environment using the repo's own scripts.

### Likely File Scope

- `tests/bootstrap.php`
- `tests/test-comment-popularity.php`
- `tests/test-visitor.php`
- `README.md`
- `CONTRIBUTING.md`

## Phase 5: Tooling And CI

Add the smallest possible automation layer that supports upstream maintenance.

### Backlog

1. Define a repeatable lint command for WordPress Coding Standards.
2. Add Composer scripts for the supported local quality commands if upstream maintainers want them.
3. Replace or supplement legacy Travis-only automation with a modest GitHub Actions workflow.
4. Keep the matrix small and realistic for an upstream plugin: one modern PHP target first, then expand only if the suite is stable.
5. Re-evaluate whether `bin/php-runtime.sh` is still needed after the Twig 3 upgrade; keep it only if it solves a reproducible host-runtime failure.

### Acceptance Criteria

- A contributor can run lint and tests with documented commands.
- CI uses the same commands documented in the repo.
- CI introduction does not block upstream review with an oversized matrix or unrelated refactors.
- Wrapper behavior and wrapper documentation match a reproducible local compatibility need.

### Likely File Scope

- `composer.json`
- `phpunit.xml`
- `.travis.yml`
- `.github/workflows/*`
- `bin/php-runtime.sh`
- `README.md`
- `CONTRIBUTING.md`
- `CHANGELOG_UNRELEASED.md`

## Phase 6: Documentation And Release Readiness

Close the loop after the code and tests are credible.

### Backlog

1. Update README installation, testing, and extension guidance to match the current code.
2. Record fixed bugs and modernization work in the changelog.
3. Document any supported-version decisions that were made during modernization.
4. Keep the roadmap updated as backlog items move upstream.

### Acceptance Criteria

- The README reflects the actual plugin behavior and supported contributor workflow.
- Each upstream PR has a matching changelog or release-note decision.
- The roadmap can be used as an issue and PR sequencing guide.

## Phase 7: Fork-Specific Modernization (Explicit PHP Floor, PSR-4, PHPStan)

This phase is fork-only work that goes beyond the upstream-friendly scope of Phases 1–6. It is where the fork would make an explicit higher PHP floor decision, introduce PSR-4 autoloading under the `CommentPopularity` namespace, and add PHPStan static analysis. The current `develop` branch has not yet enforced that higher PHP floor in code, so this phase should be treated as prospective work rather than current state. It also picks up two small bug fixes in the experts widget that were identified during quality review but are not on any existing branch.

### Backlog

1. Fix the experts widget Gravatar URL to use HTTPS instead of HTTP.
2. Initialize `$return = array()` in `get_experts()` before population to prevent undefined-variable notices.
3. Declare PHP 8.2 as the minimum in `composer.json` (`"php": "^8.2"`) and update the CI matrix.
4. Add PSR-4 autoload mapping for the `CommentPopularity` namespace in `composer.json`.
5. Add namespace declarations to class files and update the bootstrap to use the Composer autoloader.
6. Install PHPStan with WordPress stubs, create `phpstan.neon` with a baseline, and add a PHPStan job to the CI workflow.

### Acceptance Criteria

- Gravatar URLs in the experts widget use HTTPS; no mixed-content warnings on HTTPS sites.
- `get_experts()` returns an empty array (not undefined) when no experts exist.
- `composer.json` requires `"php": "^8.2"` and CI only tests PHP 8.2+.
- `composer dump-autoload` generates working PSR-4 autoloading for `CommentPopularity\` classes.
- The plugin boots and passes all existing tests using the Composer autoloader.
- PHPStan runs at level 5+ with a committed baseline; CI fails on new errors above baseline.

### Likely File Scope

- `inc/widgets/experts/class-widget-experts.php`
- `composer.json`
- `comment-popularity.php`
- `inc/class-comment-popularity.php`
- `inc/class-visitor.php`
- `admin/class-comment-popularity-admin.php`
- `phpstan.neon`
- `.github/workflows/quality.yml`

### Plans

- [ ] 07-01-PLAN.md — Remaining bug fixes and PHP 8.2 minimum declaration
- [ ] 07-02-PLAN.md — PHPStan setup and CI integration
- [ ] 07-03-PLAN.md — PSR-4 autoloading migration

## Branch Status (Phases 1–5)

Phases 1–5 are largely implemented on restacked `codex/*` branches awaiting upstream merge. See `.planning/prs/README.md` for the full PR strategy and `gh pr create` commands.

| Branch | Covers | Status |
|--------|--------|--------|
| `codex/core-correctness` | Phase 1 + Phase 2 | Ready for upstream PR |
| `codex/fix-comment-sort-signature` | Phase 3 (sort signature) | Ready, stacked on above |
| `codex/wpcs-modernization` | Phase 3 (WPCS cleanup) + Phase 5 (PHPCS config) | Ready, stacked |
| `codex/ci-quality` | Phase 5 (GitHub Actions CI) | Ready, stacked |
| `codex/wilson-activation` | Deferred (Wilson ranking) | Draft, stacked |

## Deferred Until Core Bugs Are Resolved

These are not good first upstream targets for this codebase:

- Wilson score or alternate ranking-mode work.
- Performance caching or async recalculation work.
- REST API or block-editor rewrites.
- Visual redesign of the voting UI.
- Raising minimum supported WordPress or PHP versions without explicit upstream agreement.

## Recommended PR Sequence

1. `fix(visitor): persist empty member state and preserve guest vote storage`
2. `fix(ajax): guard missing visitors and invalid comments`
3. `fix(voting): enforce vote transition rules server-side`
4. `chore(compat): PHP 8 and WPCS cleanup`
5. `test(visitor): add visitor and AJAX regression coverage`
6. `chore(ci): document local test setup and add minimal quality automation`

## Definition Of Done For This Roadmap

- The highest-risk voting bugs are fixed first.
- Each phase can be proposed upstream as one or more focused pull requests.
- Changes follow WordPress Coding Standards and do not depend on fork-only architecture.
- Phase 7 (fork-specific modernization) is tracked separately and does not block upstream work.
- The roadmap stays grounded in the code that actually exists in this repository.

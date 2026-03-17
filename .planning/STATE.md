# Project State

## Current Position

Phase: Post-Phase 7/8 stabilization
Status: Phase 8 remediation and Phase 07-03 autoload cleanup are both merged to `develop`
Last activity: 2026-03-16

## Project Reference

See: `README.md`, `ROADMAP.md`, `FORK_FIRST_POLICY.md`

**Core value:** Vote integrity — every vote is attributed to a verified identity (logged-in user or guest IP), stored consistently, and queryable via both legacy karma and Wilson confidence scoring.
**Current focus:** continue fork stabilization from `develop`, especially coverage expansion and the next selected post-Phase-8 follow-up from the roadmap.

## Accumulated Context

### Delivered Work

- **Phase 8 (Remediation):** complete. Author-karma identity, uninstall cleanup, callback coverage, guest persistence coverage, experts widget hardening, and fork-first doc reconciliation are landed.
- **Phase 7 (Modernization):** complete on `develop`.
- `07-01` outcomes are present: PHP `8.2` floor is aligned and experts widget defects are fixed.
- `07-02` outcomes are present: PHPStan, CI quality gates, and baselines are active.
- `07-03` outcomes are now present on `develop`: Composer classmap/PSR-4 autoload mapping is active, bootstrap loads `inc/lib/autoload.php`, and manual class includes are removed from plugin runtime.
- **Fork-first policy:** WordPress.org listing closed March 3, 2021. `dknauss/comment-popularity` `develop` is canonical. Upstream PRs are optional and minimized.

### Key Decisions

- Wilson ranking is implemented fork behavior — stabilization only, not feature reintroduction.
- Neutral vote state is no longer a required UX. Active vote states are `upvote` or `downvote`; same-arrow repeat clicks are rejected/no-op and opposite-arrow clicks switch directly.
- `hmn_cp_interval` filter is retired in the fork; vote integrity is enforced through explicit server-side state transition rules.
- Composer vendor lives in `inc/lib/` (non-standard but inherited from upstream).
- `bin/php-runtime.sh` cascades to Local PHP 8.4/8.3/8.2 for consistent runtime.
- Namespaced plugin classes are now loaded through Composer autoload; only non-class files such as `helpers.php` and `upgrade.php` remain explicit bootstrap includes.
- Coverage workflow is part of the canonical contributor path:
  - `composer test:setup`
  - `composer test:integration`
  - `composer test:coverage`
- Coverage threshold is currently 35%; clover output under `tests/cache/coverage/` is the execution truth for triage.

### Current Risks / Follow-Ups

- Local WP-CLI smoke checks depend on the Local MySQL socket path being live; when Local is stopped, `bin/wp-local-single-site.sh` will fail even if the site still answers cached HTTP requests.
- The next meaningful work item is no longer Phase 7 completion; it is choosing the next stabilization or coverage-expansion slice from the roadmap.
- Coverage expansion can continue incrementally, but the highest-risk public-surface gaps from Phase 8 are closed.

## Session Continuity

Current branch: `develop`
Next likely task: define the next stabilization or coverage-expansion branch from roadmap priorities
Current metrics: See `docs/current-metrics.md`

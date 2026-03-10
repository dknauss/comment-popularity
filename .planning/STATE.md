# Project State

## Current Position

Phase: 8 (Remediation)
Plan: 0 of 2 executed
Status: In progress — Phase 8 work on branch `codex/testing-related-fixes-isolated`
Last activity: 2026-03-09

## Project Reference

See: `README.md`, `ROADMAP.md`

**Core value:** Vote integrity — every vote is attributed to a verified identity (logged-in user or guest IP), stored consistently, and queryable via both legacy karma and Wilson confidence scoring.
**Current focus:** Phase 8 remediation — fix correctness bugs, close coverage gaps, reconcile docs with actual fork state.

## Accumulated Context

### Completed Phases

- **Phase 7 (Modernization):** Twig 2→3, PHPStan 1→2, WPCS 2→3, PHP floor 8.2 aligned in code/CI/docs. Complete.
- **PR series (01–05):** Core correctness fixes, sort signature, WPCS modernization, CI quality, Wilson activation draft.
- **Fork-first policy:** WordPress.org listing closed March 3, 2021. `dknauss/comment-popularity` `develop` is canonical. Upstream PRs minimized per `FORK_FIRST_POLICY.md`.

### Phase 8 Scope

Two plans:
- **08-01:** Core defect fixes (TDD-driven): author-karma identity, multisite uninstall, vote-interval contradiction, guest persistence + callback coverage, experts widget fixes, docs reconciliation.
- **08-02:** Coverage backlog ordered by runtime risk — lock blocking items, slice-specific test-first fixes, define secondary backlog completion criteria.

### Key Decisions

- Wilson ranking is implemented fork behavior — stabilization only, not feature reintroduction.
- `hmn_cp_interval` filter is dead code in current fork — Phase 8 decides: retire or implement real throttling.
- Composer vendor lives in `inc/lib/` (non-standard but inherited from upstream).
- `bin/php-runtime.sh` cascades to Local PHP 8.4/8.3/8.2 for consistent runtime.
- Coverage threshold is 35% (will be ratcheted after Phase 8 gaps close).

### Blockers/Concerns

- Phase 8 branch has uncommitted work (12 modified files + 1 new test file).
- Stash entry exists from prior session (`stash@{0}` from PHP 8.1+ alignment).

## Session Continuity

Last session: 2026-03-09
Stopped at: Phase 8 work in progress on isolated branch
Current metrics: See `docs/current-metrics.md`

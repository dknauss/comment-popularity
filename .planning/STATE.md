# Project State

## Current Position

Phase: 8 (Remediation) — complete
Plan: 2 of 2 executed
Status: Merged to `develop` via PR #22. Release prep branch `codex/release-1-5-2-prep` exists.
Last activity: 2026-03-12

## Project Reference

See: `README.md`, `ROADMAP.md`

**Core value:** Vote integrity — every vote is attributed to a verified identity (logged-in user or guest IP), stored consistently, and queryable via both legacy karma and Wilson confidence scoring.
**Current focus:** Post-Phase-8 stabilization. Release 1.5.2 prep is next.

## Accumulated Context

### Completed Phases

- **Phase 7 (Modernization):** Twig 2→3, PHPStan 1→2, WPCS 2→3, PHP floor 8.2 aligned in code/CI/docs. Complete.
- **Phase 8 (Remediation):** Widget TDD (experts widget fixes), docs sync, callback coverage, guest persistence coverage, multisite uninstall coverage, vote-interval retirement. Merged via PR #22 on 2026-03-12.
- **PR series (01–05):** Core correctness fixes, sort signature, WPCS modernization, CI quality, Wilson activation draft.
- **Fork-first policy:** WordPress.org listing closed March 3, 2021. `dknauss/comment-popularity` `develop` is canonical. Upstream PRs minimized per `FORK_FIRST_POLICY.md`.

### Key Decisions

- Wilson ranking is implemented fork behavior — stabilization only, not feature reintroduction.
- `hmn_cp_interval` filter retired as dead code (Phase 8 decision).
- Composer vendor lives in `inc/lib/` (non-standard but inherited from upstream).
- `bin/php-runtime.sh` cascades to Local PHP 8.4/8.3/8.2 for consistent runtime.
- Coverage threshold is 35% (ratchet target after Phase 8 baseline settles).

### Blockers/Concerns

None. Phase 8 branch merged, stash entries dropped (all changes incorporated into develop).

### Hygiene (2026-03-12)

- Closed obsolete PR #21 (pre-Phase-8 metrics corrections).
- Deleted 12 stale local branches (all merged into develop).
- Pruned 5 stale remote tracking refs.
- Dropped 2 obsolete stash entries (content already in develop).
- Corrected test file count (11→10) and widget type count (1→2) in `docs/current-metrics.md`.

## Session Continuity

Last session: 2026-03-12
Stopped at: Post-Phase-8 hygiene pass; release prep branch `codex/release-1-5-2-prep` exists on remote
Current metrics: See `docs/current-metrics.md`

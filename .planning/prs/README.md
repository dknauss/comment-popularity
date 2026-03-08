# Optional Upstream Export Draft Set

Prepared on 2026-03-06 for `humanmade/comment-popularity`.

## Fork-First Note

This repository is operated fork-first.

- Canonical integration branch: `develop` on `dknauss/comment-popularity`
- Primary shipping channel: this fork
- Upstream PRs: optional, minimized, and only opened when there is a clear external need

Use the drafts in this folder as export templates, not as the default development workflow. See `FORK_FIRST_POLICY.md`, `README.md`, and `CONTRIBUTING.md` for the canonical process.

## Replacement Map

1. `codex/core-correctness`
   - If an upstream export is needed, open a new PR against `master`
   - Replaces closed, unmerged `#132`
   - Title: `Harden voting flows and fix vote state persistence`

2. `codex/fix-comment-sort-signature`
   - If upstream export is needed, open after `codex/core-correctness` merges upstream
   - Supersedes open `#135`
   - Title: `Fix deprecated comment sort method signature`

3. `codex/wpcs-modernization`
   - If upstream export is needed, open after `codex/fix-comment-sort-signature` merges upstream
   - Supersedes open `#133`
   - Title: `Modernize helper APIs and add PHPCS project rules`

4. `codex/ci-quality`
   - If upstream export is needed, open after `codex/wpcs-modernization` merges upstream
   - Replaces closed, unmerged `#137`
   - Title: `Add project-aware quality checks in CI`

5. `codex/wilson-activation`
   - Keep draft until the lower queue merges
   - Supersedes open `#136`
   - Absorbs the closed, unmerged groundwork from `#134`
   - Title: `Add admin-controlled Wilson ranking activation`

## No Upstream PR Recommended

- `codex/docs-gsd-phased-plan`
  - Keep this fork-only unless Human Made explicitly asks for planning material upstream.

## Create Commands

Run these from the repo root only if you are intentionally exporting work upstream. The default path remains landing and shipping changes from fork `develop`.

Only create the next upstream PR after the predecessor has merged, since the `codex/*` branches are intentionally restacked.

```bash
gh pr create \
  --repo humanmade/comment-popularity \
  --base master \
  --head dknauss:codex/core-correctness \
  --title "Harden voting flows and fix vote state persistence" \
  --body-file .planning/prs/01-core-correctness.md
```

```bash
gh pr create \
  --repo humanmade/comment-popularity \
  --base master \
  --head dknauss:codex/fix-comment-sort-signature \
  --title "Fix deprecated comment sort method signature" \
  --body-file .planning/prs/02-sort-signature.md
```

```bash
gh pr create \
  --repo humanmade/comment-popularity \
  --base master \
  --head dknauss:codex/wpcs-modernization \
  --title "Modernize helper APIs and add PHPCS project rules" \
  --body-file .planning/prs/03-wpcs-modernization.md
```

```bash
gh pr create \
  --repo humanmade/comment-popularity \
  --base master \
  --head dknauss:codex/ci-quality \
  --title "Add project-aware quality checks in CI" \
  --body-file .planning/prs/04-ci-quality.md
```

```bash
gh pr create \
  --repo humanmade/comment-popularity \
  --base master \
  --head dknauss:codex/wilson-activation \
  --title "Add admin-controlled Wilson ranking activation" \
  --body-file .planning/prs/05-wilson-activation-draft.md \
  --draft
```

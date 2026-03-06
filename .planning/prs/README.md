# Upstream PR Draft Set

Prepared on 2026-03-06 for `humanmade/comment-popularity`.

## Replacement Map

1. `codex/core-correctness`
   - Open a new PR against `master`
   - Replaces closed, unmerged `#132`
   - Title: `Harden voting flows and fix vote state persistence`

2. `codex/fix-comment-sort-signature`
   - Open after `codex/core-correctness` merges
   - Supersedes open `#135`
   - Title: `Fix deprecated comment sort method signature`

3. `codex/wpcs-modernization`
   - Open after `codex/fix-comment-sort-signature` merges
   - Supersedes open `#133`
   - Title: `Modernize helper APIs and add PHPCS project rules`

4. `codex/ci-quality`
   - Open after `codex/wpcs-modernization` merges
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

Run these from the repo root. Only create the next PR after the predecessor has merged, since the `codex/*` branches are intentionally restacked.

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

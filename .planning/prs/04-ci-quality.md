## Summary

Fork-first note: this repository ships from the fork. Use this PR body only when there is an explicit decision to export this branch upstream.

This PR adds a project-aware quality workflow and updates the PHPUnit configuration so CI validates the plugin with the repository's own ruleset and a real WordPress test matrix.

## What Changed

- Add `.github/workflows/quality.yml` with:
  - PHPCS using `phpcs.xml.dist`
  - PHPUnit against a MySQL-backed WordPress test install
  - PHP `8.2`
  - WordPress `6.4`
- Update `phpunit.xml` to:
  - fail on risky tests and warnings
  - name the test suite
  - define a coverage include list

## Why This Is Separate

CI should enforce the project rules only after the ruleset and modernization branch are in place. Keeping this separate also avoids mixing workflow churn into the code-review branches below it.

## Testing

- Workflow YAML reviewed against the committed `phpcs.xml.dist`
- PHPUnit config updated to match the CI bootstrap flow
- Full execution is expected in GitHub Actions after the PR is opened

## Supersedes

- Replaces closed, unmerged `#137`

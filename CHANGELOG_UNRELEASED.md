Unreleased
==========

- Target version: 1.5.2-dev.
- No unreleased changes yet.

1.5.1 - 2026-03-08
==================

- Bug fix: member vote state now persists an empty array when the last logged vote is removed.
- Bug fix: repeated direct vote requests no longer desynchronize legacy karma from Wilson vote metadata.
- Tests: visitor persistence and Wilson vote transition coverage expanded.
- CI: quality workflow now uses deterministic Composer scripts and pinned WordPress test versions.
- CI: PHPCS changed-files and full-repository checks now run clean under the updated baseline ruleset.
- CI: WordPress test bootstrap no longer depends on `svn`; tests are fetched from `wordpress-develop` archives.
- Compat: `get_comments_sorted_by_weight()` accepts both legacy and modern parameter order.
- Docs: fork-first workflow policy is now codified as canonical process.

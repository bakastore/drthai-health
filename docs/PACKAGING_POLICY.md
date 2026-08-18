# Packaging Policy v0.2

This document describes the tracked Packaging Policy v0.2 implementation used to classify DrThai Health runtime artifacts.

`DENIED_TREE_ANCHOR=REPOSITORY_ROOT`

- DENY takes precedence over ALLOW.
- Unclassified files fail closed.
- Denied trees are anchored at the repository root. A nested name such as `assets/docs/` or `assets/scripts/` is not denied merely because it matches the name of a root denied tree.
- Runtime symlinks are not followed.
- Root `docs/**` and `scripts/**` trees remain denied from runtime artifacts as defined by policy.
- Implementation authority is the tracked policy code, validator and automated tests under `scripts/packaging/`.
- TEST-22, `test_22_denied_trees_are_anchored_to_repository_root`, proves repository-root anchoring behavior.

## Historical release evidence

The retained Gate 3R `packaging-policy-v0.2.txt` has SHA256 `ad2bcbe707614de0afd404f2b4c5b6a74154bffd7bfad742cf87dc3b051bc197` and is immutable release evidence. Its prose omitted an explicit textual statement of `DENIED_TREE_ANCHOR=REPOSITORY_ROOT`; the implementation, validator output and TEST-22 already enforced that behavior. This documentation clarification does not modify the retained policy file, sealed Gate evidence, Local Release Candidate ZIP or historical hashes.

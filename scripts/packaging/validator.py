#!/usr/bin/env python3
"""Deterministic, fail-closed Packaging Policy v0.2 validator."""

from __future__ import annotations

import argparse
import hashlib
import os
import re
import stat
import sys
from dataclasses import dataclass, field
from pathlib import Path, PurePosixPath

from policy import (
    ALLOW_EXACT,
    ALLOW_TREES,
    DENY_BASENAMES,
    DENY_EXACT,
    DENY_SUFFIXES,
    DENY_TREES,
    DENIED_TREE_ANCHOR,
    POLICY_VERSION,
    REQUIRED_FILES,
)

ASSET_REFERENCE_RE = re.compile(r"assets/[A-Za-z0-9._/-]+")
DEPENDENCY_SOURCE_TREES = ("parts", "patterns", "templates")


class ValidationError(RuntimeError):
    """Raised when a repository violates Packaging Policy v0.2."""


@dataclass
class ValidationResult:
    runtime: dict[str, str] = field(default_factory=dict)
    denied_files: list[str] = field(default_factory=list)
    denied_trees: list[str] = field(default_factory=list)
    empty_allow_trees: list[str] = field(default_factory=list)
    php_files: list[str] = field(default_factory=list)
    asset_references: list[str] = field(default_factory=list)

    def manifest_bytes(self) -> bytes:
        lines = [f"{path}\t{self.runtime[path]}" for path in sorted(self.runtime)]
        return (("\n".join(lines) + "\n") if lines else "").encode("utf-8")


def _parts(path: str) -> tuple[str, ...]:
    return PurePosixPath(path).parts


def _in_allow_tree(path: str) -> bool:
    parts = _parts(path)
    return bool(parts) and parts[0] in ALLOW_TREES


def _deny_tree_boundary(path: str) -> bool:
    parts = _parts(path)
    root_tree_denied = bool(parts) and parts[0] in DENY_TREES
    nested_cache_denied = any(part == "__pycache__" for part in parts)
    return root_tree_denied or nested_cache_denied


def _repository_metadata(path: str) -> bool:
    return any(part.startswith(".git") for part in _parts(path))


def deny_reason(path: str) -> str | None:
    basename = PurePosixPath(path).name
    if _repository_metadata(path):
        return "repository-metadata"
    if _deny_tree_boundary(path):
        return "denied-tree"
    if path in DENY_EXACT:
        return "deny-exact"
    if basename == ".env" or basename.startswith(".env."):
        return "secret-env"
    if basename in DENY_BASENAMES:
        return "residue-basename"
    if basename.endswith("~") or basename.lower().endswith(DENY_SUFFIXES):
        return "residue-suffix"
    return None


def allowed(path: str) -> bool:
    return path in ALLOW_EXACT or _in_allow_tree(path)


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def _scan(root: Path, result: ValidationResult) -> list[str]:
    errors: list[str] = []

    def visit(directory: Path, relative: str = "") -> None:
        try:
            entries = sorted(os.scandir(directory), key=lambda entry: entry.name)
        except OSError as exc:
            errors.append(f"SCAN_ERROR={relative or '.'}:{exc}")
            return

        for entry in entries:
            rel = f"{relative}/{entry.name}" if relative else entry.name
            rel = PurePosixPath(rel).as_posix()
            try:
                mode = entry.stat(follow_symlinks=False).st_mode
            except OSError as exc:
                errors.append(f"STAT_ERROR={rel}:{exc}")
                continue

            is_allowed_area = _in_allow_tree(rel) or rel in ALLOW_EXACT
            reason = deny_reason(rel)

            if stat.S_ISLNK(mode):
                if is_allowed_area:
                    errors.append(f"ALLOWED_AREA_SYMLINK={rel}")
                else:
                    result.denied_files.append(rel)
                continue

            if stat.S_ISDIR(mode):
                if reason:
                    result.denied_trees.append(rel)
                    continue
                visit(Path(entry.path), rel)
                continue

            if not stat.S_ISREG(mode):
                if is_allowed_area:
                    errors.append(f"ALLOWED_AREA_SPECIAL_FILE={rel}")
                else:
                    errors.append(f"UNCLASSIFIED_SPECIAL_FILE={rel}")
                continue

            if reason:
                result.denied_files.append(rel)
            elif allowed(rel):
                result.runtime[rel] = _sha256(Path(entry.path))
            else:
                errors.append(f"UNCLASSIFIED_FILE={rel}")

    visit(root)
    return errors


def _check_required(root: Path, result: ValidationResult) -> list[str]:
    errors = []
    for required in sorted(REQUIRED_FILES):
        source = root / required
        if required not in result.runtime or not source.is_file() or source.is_symlink():
            errors.append(f"MISSING_REQUIRED_FILE={required}")
    return errors


def _check_allow_trees(root: Path, result: ValidationResult) -> None:
    for tree in sorted(ALLOW_TREES):
        source = root / tree
        if source.is_dir() and not source.is_symlink():
            prefix = tree + "/"
            if not any(path.startswith(prefix) for path in result.runtime):
                result.empty_allow_trees.append(tree)


def _literal_asset_references(root: Path, result: ValidationResult) -> list[str]:
    errors: list[str] = []
    references: set[str] = set()
    for tree in DEPENDENCY_SOURCE_TREES:
        prefix = tree + "/"
        for rel in sorted(path for path in result.runtime if path.startswith(prefix)):
            source = root / rel
            try:
                text = source.read_text(encoding="utf-8")
            except UnicodeDecodeError:
                continue
            references.update(ASSET_REFERENCE_RE.findall(text))
    result.asset_references = sorted(references)
    for reference in result.asset_references:
        source = root / reference
        if reference not in result.runtime or not source.is_file() or source.is_symlink():
            errors.append(f"MISSING_LITERAL_ASSET_REFERENCE={reference}")
    return errors


def validate(root: Path) -> ValidationResult:
    root = root.resolve()
    result = ValidationResult()
    errors = _scan(root, result)
    errors.extend(_check_required(root, result))
    _check_allow_trees(root, result)
    errors.extend(_literal_asset_references(root, result))
    result.denied_files.sort()
    result.denied_trees.sort()
    result.php_files = sorted(path for path in result.runtime if path.endswith(".php"))
    if errors:
        raise ValidationError("\n".join(sorted(set(errors))))
    return result


def render_report(result: ValidationResult) -> str:
    lines = [
        f"POLICY_VERSION={POLICY_VERSION}",
        f"DENIED_TREE_ANCHOR={DENIED_TREE_ANCHOR}",
        "VALIDATION=PASS",
        f"CURRENT_RUNTIME_FILE_COUNT={len(result.runtime)}",
        "UNCLASSIFIED_FILES=NONE",
        "REQUIRED_RUNTIME_GATE=PASS",
        "SYMLINK_GATE=PASS",
        "SPECIAL_FILE_GATE=PASS",
    ]
    lines.extend(f"DENIED_TREE={path}\nTRAVERSED=false" for path in result.denied_trees)
    lines.extend(f"ALLOW_TREE_EMPTY_AFTER_DENY={path}" for path in result.empty_allow_trees)
    lines.append(f"RUNTIME_PHP_FILE_COUNT={len(result.php_files)}")
    lines.append("RUNTIME_PHP_FILES:")
    lines.extend(result.php_files or ["NONE"])
    lines.append("LITERAL_ASSET_REFERENCES:")
    lines.extend(result.asset_references or ["NONE"])
    lines.append("DEPENDENCY_REFERENCE_GATE=PASS")
    lines.append(
        "DEPENDENCY_LIMITATION=literal assets/** references in patterns/**, templates/**, parts/** only"
    )
    return "\n".join(lines) + "\n"


def main(argv: list[str] | None = None) -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--root", type=Path, required=True)
    parser.add_argument("--manifest", type=Path)
    args = parser.parse_args(argv)
    try:
        result = validate(args.root)
        if args.manifest:
            args.manifest.write_bytes(result.manifest_bytes())
        sys.stdout.write(render_report(result))
        return 0
    except ValidationError as exc:
        sys.stderr.write(f"POLICY_VERSION={POLICY_VERSION}\nVALIDATION=FAIL\n{exc}\n")
        return 1


if __name__ == "__main__":
    raise SystemExit(main())

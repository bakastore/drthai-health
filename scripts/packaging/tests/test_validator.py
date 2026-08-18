from __future__ import annotations

import contextlib
import errno
import io
import os
import socket
import sys
import tempfile
import unittest
from pathlib import Path

PACKAGING_DIR = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(PACKAGING_DIR))

from validator import ValidationError, main, render_report, validate  # noqa: E402


class ValidatorTests(unittest.TestCase):
    def fixture(self, order: tuple[str, ...] | None = None) -> Path:
        root = Path(tempfile.mkdtemp(prefix="drthai-packaging-test-"))
        files = order or ("functions.php", "style.css", "theme.json", "templates/index.html")
        for rel in files:
            path = root / rel
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(rel + "\n", encoding="utf-8")
        self.addCleanup(lambda: self._remove(root))
        return root

    @staticmethod
    def _remove(root: Path) -> None:
        for path in sorted(root.rglob("*"), key=lambda p: len(p.parts), reverse=True):
            if path.is_symlink() or not path.is_dir():
                path.unlink(missing_ok=True)
            elif path.is_dir():
                path.rmdir()
        root.rmdir()

    def assert_invalid(self, root: Path, marker: str) -> None:
        with self.assertRaisesRegex(ValidationError, marker):
            validate(root)

    def test_01_valid_minimal_runtime_tree(self):
        self.assertEqual(4, len(validate(self.fixture()).runtime))

    def test_02_deny_wins_inside_allow_tree(self):
        root = self.fixture(); (root / "assets").mkdir(); (root / "assets/foo.tmp").write_text("x")
        result = validate(root)
        self.assertIn("assets/foo.tmp", result.denied_files)
        self.assertNotIn("assets/foo.tmp", result.runtime)

    def test_03_unclassified_exits_nonzero(self):
        root = self.fixture(); (root / "mystery.xyz").write_text("x")
        with contextlib.redirect_stderr(io.StringIO()):
            self.assertNotEqual(0, main(["--root", str(root)]))

    def test_04_missing_templates_index_fails(self):
        self.assert_invalid(self.fixture(("functions.php", "style.css", "theme.json")), "MISSING_REQUIRED_FILE=templates/index.html")

    def test_05_optional_screenshot_missing_passes(self):
        self.assertNotIn("screenshot.png", validate(self.fixture()).runtime)

    def test_06_symlink_inside_assets_fails_without_following(self):
        root = self.fixture(); target = root.parent / (root.name + "-target")
        target.write_text("outside"); self.addCleanup(target.unlink)
        (root / "assets").mkdir(); (root / "assets/link").symlink_to(target)
        self.assert_invalid(root, "ALLOWED_AREA_SYMLINK=assets/link")

    def test_07_symlink_inside_denied_tree_is_pruned(self):
        root = self.fixture(); target = root.parent / (root.name + "-target")
        target.write_text("outside"); self.addCleanup(target.unlink)
        (root / "node_modules").mkdir(); (root / "node_modules/link").symlink_to(target)
        result = validate(root)
        self.assertIn("node_modules", result.denied_trees)

    @unittest.skipUnless(hasattr(os, "mkfifo"), "FIFO unsupported")
    def test_08_fifo_inside_allowed_area_fails(self):
        root = self.fixture(); (root / "assets").mkdir(); special = root / "assets/special"
        try:
            os.mkfifo(special)
        except OSError as exc:
            if exc.errno not in {errno.ENOTSUP, errno.EOPNOTSUPP}:
                raise
            unix_socket = socket.socket(socket.AF_UNIX)
            self.addCleanup(unix_socket.close)
            try:
                unix_socket.bind(str(special))
            except OSError as socket_exc:
                if socket_exc.errno not in {errno.ENOTSUP, errno.EOPNOTSUPP}:
                    raise
                self.skipTest("fixture filesystem supports neither FIFO nor AF_UNIX socket")
        self.assert_invalid(root, "ALLOWED_AREA_SPECIAL_FILE=assets/special")

    def test_09_nested_pycache_is_denied(self):
        root = self.fixture(); cache = root / "foo/bar/__pycache__"; cache.mkdir(parents=True)
        (cache / "x.pyc").write_bytes(b"cache")
        result = validate(root)
        self.assertIn("foo/bar/__pycache__", result.denied_trees)

    def test_10_secret_files_are_denied(self):
        root = self.fixture()
        for rel in (".env", ".env.production", "secret.pem", "private.key"):
            (root / rel).write_text("secret")
        result = validate(root)
        self.assertTrue({".env", ".env.production", "secret.pem", "private.key"}.issubset(result.denied_files))

    def test_11_same_tree_twice_is_identical(self):
        root = self.fixture(); self.assertEqual(validate(root).manifest_bytes(), validate(root).manifest_bytes())

    def test_12_creation_order_does_not_change_output(self):
        files = ("theme.json", "templates/index.html", "functions.php", "style.css")
        first = self.fixture(files); second = self.fixture(tuple(reversed(files)))
        self.assertEqual(validate(first).manifest_bytes(), validate(second).manifest_bytes())

    def test_13_functions_missing_fails(self):
        self.assert_invalid(self.fixture(("style.css", "theme.json", "templates/index.html")), "MISSING_REQUIRED_FILE=functions.php")

    def test_14_theme_json_missing_fails(self):
        self.assert_invalid(self.fixture(("functions.php", "style.css", "templates/index.html")), "MISSING_REQUIRED_FILE=theme.json")

    def test_15_style_css_missing_fails(self):
        self.assert_invalid(self.fixture(("functions.php", "theme.json", "templates/index.html")), "MISSING_REQUIRED_FILE=style.css")

    def test_16_validator_tree_is_denied(self):
        root = self.fixture(); path = root / "scripts/packaging"; path.mkdir(parents=True)
        (path / "validator.py").write_text("tool")
        self.assertIn("scripts", validate(root).denied_trees)

    def test_17_repository_metadata_semantics(self):
        root = self.fixture()
        for rel in (".gitignore", ".gitattributes", ".gitmodules", ".gitkeep"):
            (root / rel).write_text("metadata")
        for rel in (".git", ".github"):
            path = root / rel; path.mkdir(); (path / "x").write_text("metadata")
        result = validate(root)
        self.assertTrue({".gitignore", ".gitattributes", ".gitmodules", ".gitkeep"}.issubset(result.denied_files))
        self.assertTrue({".git", ".github"}.issubset(result.denied_trees))

    def test_18_assets_gitkeep_denied_and_tree_reported_empty(self):
        root = self.fixture(); (root / "assets").mkdir(); (root / "assets/.gitkeep").write_text("")
        result = validate(root)
        self.assertIn("assets/.gitkeep", result.denied_files)
        self.assertIn("assets", result.empty_allow_trees)
        self.assertIn("ALLOW_TREE_EMPTY_AFTER_DENY=assets", render_report(result))

    def test_19_literal_asset_reference_resolves(self):
        root = self.fixture(); (root / "patterns").mkdir(); (root / "assets").mkdir()
        (root / "patterns/example.php").write_text("assets/image.svg")
        (root / "assets/image.svg").write_text("<svg/>")
        self.assertIn("assets/image.svg", validate(root).asset_references)

    def test_20_missing_literal_asset_reference_fails(self):
        root = self.fixture(); (root / "patterns").mkdir()
        (root / "patterns/example.php").write_text("assets/missing.svg")
        self.assert_invalid(root, "MISSING_LITERAL_ASSET_REFERENCE=assets/missing.svg")

    def test_21_php_surface_is_stable_and_sorted(self):
        root = self.fixture(); (root / "patterns").mkdir()
        (root / "patterns/z.php").write_text("z"); (root / "patterns/a.php").write_text("a")
        result = validate(root)
        self.assertEqual(sorted(result.php_files), result.php_files)
        self.assertEqual(result.php_files, validate(root).php_files)

    def test_22_denied_trees_are_anchored_to_repository_root(self):
        root = self.fixture()
        nested_files = ("assets/scripts/main.js", "assets/docs/example.json")
        for rel in nested_files:
            path = root / rel
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(rel + "\n", encoding="utf-8")
        result = validate(root)
        for rel in nested_files:
            self.assertIn(rel, result.runtime)
if __name__ == "__main__":
    unittest.main(verbosity=2)

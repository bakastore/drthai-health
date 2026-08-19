#!/usr/bin/env python3
"""Targeted source assertions for Development 1.2.1 / B3."""

import json
import re
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
tests_run = 0


def assert_true(condition: bool, label: str) -> None:
    global tests_run
    tests_run += 1
    if not condition:
        raise AssertionError(f"FAIL {label}")
    print(f"PASS {label}")


theme = json.loads((ROOT / "theme.json").read_text(encoding="utf-8"))
css = (ROOT / "style.css").read_text(encoding="utf-8")
header = (ROOT / "parts/header.html").read_text(encoding="utf-8")
palette = theme["settings"]["color"]["palette"]
expected_slugs = {
    "background", "background-alt", "ink", "ink-soft", "primary",
    "primary-dark", "sage", "sage-pale", "accent", "accent-dark", "white",
}

assert_true(len(palette) == 11 and {color["slug"] for color in palette} == expected_slugs, "theme.json exposes exactly eleven canonical palette slugs")
for slug in sorted(expected_slugs):
    alias = slug.replace("background", "bg") if slug.startswith("background") else slug
    expected = f"--drthai-{alias}: var(--wp--preset--color--{slug});"
    assert_true(expected in css, f"DrThai {slug} alias points to its WordPress preset")

duplicate_hex = {color["color"].lower() for color in palette}
style_hex = {literal.lower() for literal in re.findall(r"#[0-9a-fA-F]{3,8}\b", css)}
assert_true(not duplicate_hex.intersection(style_hex), "canonical palette HEX values are not duplicated in public CSS")
assert_true("rgba(10, 64, 58" not in css and "rgba(14, 92, 82" not in css and "rgba(217, 164, 65" not in css and "rgba(143, 183, 154" not in css, "known stale brand RGBA values are removed from public CSS")
assert_true("color-mix(in srgb, var(--drthai-ink) 12%, transparent)" in css, "line treatment derives from the canonical ink token")
assert_true("color-mix(in srgb, var(--drthai-primary-dark) 35%, transparent)" in css, "card shadow derives from the canonical primary-dark token")

gradients = {gradient["slug"]: gradient["gradient"] for gradient in theme["settings"]["color"]["gradients"]}
assert_true("var(--wp--preset--color--primary)" in gradients["clinical"] and "var(--wp--preset--color--primary-dark)" in gradients["clinical"], "clinical gradient derives from WordPress presets")
assert_true("var(--wp--preset--color--background)" in gradients["warm"] and "var(--wp--preset--color--background-alt)" in gradients["warm"], "warm gradient derives from WordPress presets")
card_shadow = theme["settings"]["shadow"]["presets"][0]["shadow"]
assert_true("var(--wp--preset--color--primary-dark)" in card_shadow and "color-mix(" in card_shadow, "theme card shadow derives from the primary-dark preset")
assert_true("var(--wp--preset--color--primary)" in header and "var(--wp--preset--color--accent)" in header, "inline logo consumes WordPress palette presets")

remaining_literals = set(re.findall(r"#[0-9a-fA-F]{3,8}\b", css))
assert_true(remaining_literals == {"#e7eeeb", "#fbe8e4", "#8a2d1e"}, "remaining public literals are limited to documented non-brand media and error colors")

changed = subprocess.run(
    ["git", "diff", "--name-only", "main"],
    cwd=ROOT,
    check=True,
    capture_output=True,
    text=True,
).stdout.splitlines()
admin_css = {"assets/css/admin-dashboard.css", "assets/css/admin-chrome.css", "assets/css/editorial-admin.css"}
assert_true(admin_css.isdisjoint(changed), "accepted Admin CSS surfaces are untouched")

print(f"B3_TESTS_RUN={tests_run}")
print("B3_TEST_STATUS=PASS")

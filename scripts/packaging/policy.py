"""Packaging Policy v0.2 for the DrThai Health runtime theme artifact."""

POLICY_VERSION = "v0.2"

ALLOW_EXACT = {
    "functions.php",
    "screenshot.png",
    "style.css",
    "theme.json",
}

ALLOW_TREES = (
    "assets",
    "inc",
    "parts",
    "patterns",
    "templates",
)

DENY_EXACT = {
    ".wp-env.json",
    ".wp-env.override.json",
    "AGENTS.md",
    "CHANGELOG.txt",
    "CLAUDE.local.md",
    "README.txt",
    "package-lock.json",
    "package.json",
}

DENY_TREES = {
    ".agents",
    ".claude",
    ".git",
    "coverage",
    "docs",
    "node_modules",
    "scripts",
}

DENY_SUFFIXES = (
    ".pem",
    ".key",
    ".bak",
    ".orig",
    ".tmp",
    ".log",
    ".swp",
    ".swo",
    ".rej",
    ".patch",
    ".diff",
    ".old",
    ".save",
    ".cache",
    ".pyc",
    ".pyo",
    ".pyd",
)

DENY_BASENAMES = {".DS_Store", "Thumbs.db"}

REQUIRED_FILES = {
    "functions.php",
    "style.css",
    "templates/index.html",
    "theme.json",
}

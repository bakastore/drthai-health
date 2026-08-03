# DrThai Health Repository Instructions

## Project scope

- This repository contains the `drthai-health` WordPress Block Theme.
- Local Development uses WordPress 7.0.2, PHP 8.3 and `@wordpress/env` 11.7.0.
- Test locally at `http://localhost:8888`.
- Work only on Local Development unless the user explicitly authorizes packaging or deployment.
- Never access or modify WordPress Production from this repository workflow.
- Never store real patient data, medical records, credentials or secrets in the repository.

## Architecture invariants

- Preserve the existing WordPress Block Theme architecture:
  - `theme.json`
  - `templates/`
  - `parts/`
  - `patterns/`
  - `functions.php`
  - `style.css`
  - `assets/`
- Prefer native Gutenberg blocks, block markup, PHP, CSS and minimal vanilla JavaScript.
- Do not introduce React, Tailwind CSS, Bootstrap, page builders, build frameworks or new runtime dependencies without explicit approval.
- Do not modify WordPress Core.
- Do not hard-code Production domains, IP addresses, credentials or environment-specific paths.
- Use official WordPress APIs, hooks, filters and enqueue functions.
- Validate and sanitize input, escape output, check capabilities and use nonces for state-changing actions.
- Keep presentation in the theme. Flag functionality that should survive a theme change before moving it into a plugin.

## UI/UX skills

- For UI/UX tasks, use `@ui-ux-pro-max` and only the relevant installed supporting skill.
- Treat skill output as design advice, not as architectural authority.
- Inspect the existing design, templates and `theme.json` before proposing changes.
- Reject recommendations that require replacing the WordPress architecture or adding an unapproved framework.
- Preserve the DrThai Health identity: professional, trustworthy, medical, calm, green and white.
- Design mobile-first and verify mobile, tablet and desktop layouts.
- Target WCAG 2.2 AA accessibility:
  - sufficient color contrast;
  - visible keyboard focus;
  - semantic structure;
  - touch targets of at least 44 by 44 pixels;
  - reduced-motion support;
  - no horizontal scrolling.
- Do not use emoji as interface icons.
- Avoid decorative motion that delays reading or obscures medical information.

## Medical content safety

- Never invent qualifications, treatment outcomes, statistics, testimonials or medical evidence.
- Never claim guaranteed treatment, “100% cure” or replacement of professional diagnosis.
- Treat AI-generated medical content as a draft requiring doctor review.
- Do not auto-publish medical content.
- Use an appropriate medical disclaimer when content may be interpreted as personal medical advice.

## Workflow and safety

- Inspect relevant files and run `git status --short` before editing.
- Work one checkpoint and one root cause at a time.
- Prefer the smallest coherent change and preserve unrelated user changes.
- Explain material design or architecture trade-offs before implementation.
- Do not run destructive Git commands.
- Do not run `wp-env destroy`, `wp-env clean` or `wp-env reset` without explicit approval and a recovery plan.
- Do not install dependencies, commit, push, package a ZIP or deploy without explicit user authorization.
- Never include `.agents/`, `node_modules/`, local configuration or development-only files in the Production theme ZIP.
- If `wp-env` must be started in this WSL2 environment, use:
  `NODE_OPTIONS=--no-network-family-autoselection npx wp-env start`

## Verification

After changes:

- Run `git diff --check`.
- Perform syntax checks on changed PHP, JSON, CSS and JavaScript files.
- Verify the theme on WordPress Local.
- Check affected pages at mobile and desktop widths.
- Confirm there is no visible PHP warning, debug output or browser console error caused by the change.
- Report changed files, verification performed and remaining risks.
# DrThai Health Repository Instructions

## 1. Authority and SSOT

Before substantive project work, read:

1. `docs/PROJECT_STATE.md`
2. `docs/ROADMAP.md`
3. `docs/ACCEPTANCE_GATES.md`

Authority rules:

- The latest explicit Owner instruction governs the current mission unless it conflicts with an unchanged protected Owner-controlled boundary.
- Newer verified repository evidence is authoritative for current factual state.
- `PROJECT_STATE.md` is the maintained factual state and is CODEX-MAINTAINABLE.
- `ROADMAP.md` is OWNER-CONTROLLED and read only unless explicitly authorized.
- `ACCEPTANCE_GATES.md` is OWNER-CONTROLLED; mandatory assertions must not be weakened without explicit Owner approval.
- Historical chat, memory and older notes are secondary to verified repository evidence and current SSOT.

If repository evidence conflicts with SSOT, report the discrepancy, use newer verified evidence for factual state, and do not silently rewrite Owner-controlled documents.

Do not duplicate volatile facts here. Runtime versions, branch, release state, phase and priorities belong in `PROJECT_STATE.md` or verified repository evidence.

## 2. Project scope

- This repository contains DrThai Health WordPress application development source: runtime Block Theme, development tooling, tests, packaging controls and project documentation.
- Normal work is Local Development using the current runtime defined by `PROJECT_STATE.md` or verified repository evidence.
- Production is DEFERRED by default. Do not access, deploy to or mutate Production unless the Owner explicitly opens a Production mission.
- Never store real identifiable patient data, medical records, credentials or secrets in the repository.
- Only files classified by the approved packaging policy may enter a runtime release artifact.

## 3. Architecture and engineering invariants

- Preserve the existing WordPress Block Theme architecture where applicable: `theme.json`, `templates/`, `parts/`, `patterns/`, `functions.php`, `style.css`, `assets/`.
- Prefer WordPress Native capabilities, Gutenberg blocks, block markup, PHP, CSS and minimal vanilla JavaScript.
- Prefer: WordPress Native → Simple → Maintainable → Testable → Reversible.
- Build for confirmed requirements, not hypothetical future requirements.
- Prefer the smallest coherent change that fully satisfies the mission.
- Do not introduce React, Tailwind CSS, Bootstrap, page builders, build frameworks, complex workflow engines or new runtime dependencies without demonstrated need and explicit approval.
- Do not introduce a custom CMS, Headless WordPress, Elasticsearch, microservices, a custom SEO engine or speculative architecture without demonstrated need and Owner approval.
- Do not modify WordPress Core.
- Do not hard-code Production domains, IPs, credentials, environment-specific filesystem paths or numeric environment-specific Page IDs in runtime logic.
- Use official WordPress APIs, hooks, filters and enqueue functions.
- Validate and sanitize input, escape output, check capabilities and use nonces for state-changing actions.
- Keep presentation in the theme. Flag functionality that should survive a theme change before moving it into a plugin.
- Do not reintroduce the legacy shortcode-based Content Engine, `drthai_news_hub`, or runtime automatic content bootstrap.
- Preserve unrelated user changes and unrelated working-tree state.

## 4. Protected boundaries

### Booking

- Booking is outside normal Content Operations work.
- Do not modify Booking unless an Owner-approved Booking mission explicitly authorizes it.
- Preserve applicable Booking acceptance boundaries and protected identifiers.

### Production

- Do not deploy, build a Final Production ZIP, change Production data, domain, DNS, HTTPS, hardening, backup or monitoring unless explicitly authorized for that mission.

### Governance documents

- `PROJECT_STATE.md`: Codex may update verified factual state after an approved mission, but must not silently change Owner priorities, scope, security boundaries or Production authorization.
- `ROADMAP.md`: read only unless explicitly authorized.
- `ACCEPTANCE_GATES.md`: read only unless explicitly authorized; never weaken mandatory assertions merely to obtain PASS.

## 5. Medical content safety

- Never invent qualifications, treatment outcomes, statistics, testimonials or medical evidence.
- Never claim guaranteed treatment, “100% cure” or replacement of professional diagnosis.
- Treat AI-generated medical content as a draft requiring doctor review.
- Do not auto-publish medical content.
- Use an appropriate medical disclaimer when content may be interpreted as personal medical advice.
- Do not use real identifiable patient data in content, fixtures, tests or examples.

## 6. UI/UX guardrails

Apply these only to an Owner-approved UI/UX mission.

- Use `@ui-ux-pro-max` when available and relevant; treat skill output as design advice, not architectural authority.
- Inspect the existing design, templates and `theme.json` first.
- Preserve the DrThai Health identity: professional, trustworthy, medical, calm, green and white.
- Reject recommendations that replace the WordPress architecture or add an unapproved framework.
- Design mobile-first when public UI is affected.
- Target WCAG 2.2 AA: sufficient contrast, visible keyboard focus, semantic structure, practical 44×44 px touch targets, reduced-motion support and no horizontal scrolling.
- Do not use emoji as interface icons or decorative motion that delays reading or obscures medical information.

Public UI/UX redesign is not implied by a Content Operations mission. Follow current SSOT priority.

## 7. Normal Mission Execution

For an Owner-approved development mission, complete the full coherent mission without pausing for routine reversible implementation decisions.

1. Read applicable SSOT and task-specific Owner instructions.
2. Inspect branch, HEAD, `git status --short` and relevant files.
3. Confirm scope against protected boundaries.
4. Perform focused discovery before editing.
5. Implement the smallest sufficient solution.
6. Run targeted verification for the changed surface.
7. Debug and self-repair in-scope failures.
8. Repeat targeted verification until acceptance passes or a real blocker is proven.
9. Run broader regression only when justified by impact.
10. Review `git diff`, `git diff --check` and final `git status --short`.
11. Update `PROJECT_STATE.md` only when verified factual state materially changes.
12. Create an atomic local commit when the approved mission permits normal implementation and acceptance passes.
13. Return one concise Completion Report.

Do not pause for routine choices such as file placement, naming, hook selection, small refactors or test organization when they are reversible and within scope.

## 8. Self-repair authority

Within an approved mission, Codex may autonomously:

- inspect relevant files and repository history;
- run non-destructive diagnostics;
- modify in-scope files;
- add or update targeted tests;
- run existing Local Development/test tooling;
- diagnose and fix in-scope failures;
- perform necessary small refactors;
- rerun targeted verification;
- create the approved local atomic commit after PASS.

Self-repair must not expand product scope, change Owner-controlled decisions, weaken tests, delete/recreate existing content without authorization, modify Production, bypass security boundaries or discard unrelated work.

When debugging an unknown failure, isolate one root cause at a time and do not change multiple independent variables simultaneously.

## 9. Stop conditions

Stop and report `BLOCKED` only when:

- an Owner-level product, architecture or governance decision is required;
- Owner-controlled requirements materially conflict;
- a destructive or irreversible operation is required;
- unauthorized Production mutation would be required;
- a protected Booking/security boundary must change;
- scope expansion is necessary;
- required evidence cannot be obtained with available repository/runtime access;
- continuing risks data loss or unrelated changes.

A blocker report must include exact evidence, impact, minimal options, recommendation and safely completed work.

## 10. Git and destructive-operation safety

For approved development missions, local atomic commits are allowed after acceptance passes unless the task explicitly forbids committing.

Never without explicit Owner approval:

- `git push`;
- create or push tags;
- merge to `main`;
- force push;
- rewrite shared Git history;
- run destructive Git operations that discard work.

Do not run `wp-env destroy`, `wp-env clean` or `wp-env reset` without explicit Owner approval and a recovery plan.

Do not install new dependencies, build a Final Production ZIP or deploy unless explicitly authorized.

Never include `.agents/`, `node_modules/`, local configuration, denied docs/tooling trees or development-only files in a Production runtime artifact.

If `wp-env` must be started in this WSL2 environment, use the repository-established command unless newer verified evidence supersedes it. Current fallback:

`NODE_OPTIONS=--no-network-family-autoselection npx wp-env start`

## 11. Risk-based verification

Do not automatically rerun every historical gate after every change.

Always perform:

- `git diff --check`;
- syntax/static checks appropriate to changed files;
- targeted functional tests for changed behavior;
- applicable mission acceptance assertions;
- final `git status --short`.

Expand verification by changed surface:

- UI → affected pages, relevant viewport checks, accessibility-sensitive behavior and browser console.
- PHP/runtime logic → PHP syntax plus targeted functional/runtime tests.
- JavaScript → available syntax/lint plus targeted browser behavior and console.
- Routing → relevant HTTP/content matrix and rewrite behavior.
- Taxonomy/content migration → data integrity, idempotency and no silent deletion/recreation.
- Packaging → Packaging Policy and Packaging Validator.
- Booking → applicable protected Booking gate.
- Shared/cross-cutting runtime architecture → broader regression proportional to impact.

Previously PASS gates must not be reopened or fully rerun unless the mission touches their protected surface, a relevant dependency changed, or new evidence indicates regression.

Never modify a test or acceptance assertion merely to hide a real regression.

## 12. Completion Report

Return one concise report:

`STATUS:` PASS / BLOCKED / PARTIAL

`SUMMARY:` what changed and why it is the smallest sufficient solution.

`FILES_CHANGED:` relevant files only.

`TESTS:` commands/checks and PASS/FAIL results.

`ACCEPTANCE:` mission assertions and results.

`REGRESSION:` relevant regression result; note unaffected PASS gates intentionally not rerun.

`GIT:` branch, relevant HEAD state, working tree and local commit hash if created.

`SSOT:` any `PROJECT_STATE.md` factual update or detected discrepancy.

`RISKS / LIMITATIONS:` real remaining issues only.

`NEXT:` one recommended next action.

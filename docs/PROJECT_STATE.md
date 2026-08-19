# DrThai Health — Project State

Status: CODEX-MAINTAINABLE
Purpose: Current factual state of the project

Last updated: 2026-08-19
Current development version: 1.2.0
Current phase: Development Acceptance 2.0 — External Review
Production deployment: DEFERRED

---

## 1. Project Objective

DrThai Health is a professional WordPress medical publishing website.

Primary goals:

- professional medical article publishing
- easy WordPress administration
- structured categories and tags
- editorial workflow
- media management
- content lifecycle management
- SEO and content discovery
- maintainable long-term operations

The system is not an EMR, HIS, patient portal, or patient record system.

No real identifiable patient data may be stored.

---

## 2. Repository

Repository:

/home/blockchain/projects/drthai-health

Current branch:

gate/development-acceptance-2.0

Last confirmed Local Development Release commit:

1f5ca5fb0d77ce5e0d5e3df9d230add50227ce16

Git policy:

- Local atomic commits are allowed for approved development missions.
- Do not push without Owner approval.
- Do not tag without Owner approval.
- Do not merge to main without Owner approval.
- Do not rewrite shared Git history.

Repository evidence is authoritative if it differs from this file.

---

## 3. Local Development Environment

Local WordPress Core:

7.0.4

Local development URL:

http://localhost:8888

Production is currently outside normal Development scope.

Production Core parity verification is deferred until Production Predeploy.

---

## 4. Content Engine 1.1.0

Status:

PASS / LOCAL DEVELOPMENT RELEASE COMPLETE

Completed:

- T3 Packaging Policy + Validator
- T4-PRE Local Content Routing Migration
- T4 Native Content Engine
- T6 Development Content Seed
- T5 Runtime Content Bootstrap Removal
- T7 Code Organization Review
- Gate 3R Local Development Release

T7 result:

NO_OP — architecture already acceptable

---

## 5. Current WordPress Routing

Current Local routing:

show_on_front=page
page_on_front=6
page_for_posts=9
permalink_structure=/tin-tuc/%postname%/
category_base=chuyen-muc
tag_base=tu-khoa

Current semantic routes:

- / = Static Front Page
- /tin-tuc/ = native WordPress Posts Page
- /tin-tuc/<post-slug>/ = Single Post
- /chuyen-muc/<slug>/ = Category archive
- /tu-khoa/<slug>/ = Tag archive
- Search = native WordPress search
- 404 = native theme 404 template

Do not hard-code environment-specific Page IDs in runtime logic.

---

## 6. Content Architecture

Native WordPress Content Engine is active.

Legacy News Hub:

Runtime drthai_news_hub references = 0

Runtime automatic content bootstrap:

ABSENT

Do not reintroduce the legacy shortcode-based Content Engine.

---

## 7. Development Seed

Development seed exists and is explicit and idempotent.

Last verified repeat execution:

CREATE=0

Approved category addition:

tin-hoat-dong

Seed rules:

- semantic resolution
- no fixed numeric IDs
- no deletion of existing content
- no overwrite of existing real content
- repeated execution must not create duplicates
- development seed must remain excluded from runtime package

---

## 8. Booking Boundary

Booking is outside Content Engine refactoring.

Last verified:

Booking functional gate = PASS
Booking protected boundary = PASS
Booking identifiers = PASS

Do not modify Booking unless an Owner-approved Booking task explicitly authorizes it.

Booking plugin extraction remains outside Content Engine 1.1.0.

---

## 9. Packaging

Packaging Policy:

v0.2

Core rules:

- DENY takes precedence over ALLOW
- UNCLASSIFIED means FAIL CLOSED
- denied trees are anchored at repository root
- runtime symlinks are not followed
- development tooling must not enter Production runtime package

Last Local Release verification:

Packaging tests = PASS
Packaging validator = PASS
Unclassified files = 0
Runtime files = 32
Runtime PHP files = 12
Unpack versus Manifest verification = PASS

Known accepted limitation:

The special-file fixture test may be skipped if the local filesystem
cannot create FIFO or AF_UNIX socket entries.

This does not permit special files in runtime artifacts.

---

## 10. Local Release Candidate 1.1.0

Release commit:

1f5ca5fb0d77ce5e0d5e3df9d230add50227ce16

Artifact:

/home/blockchain/releases/drthai-health/1.1.0-local/drthai-health-1.1.0.zip

Artifact SHA256:

ef1bac3796ed049b2ddee5424316a38334d9ab3a42cc90148e51565c8abc9bab

Manifest SHA256:

4827bb43c4d8111595e285c94e708e5dab920eee7651d8bd3d0e466ca47a0624

Packaging Policy SHA256:

ad2bcbe707614de0afd404f2b4c5b6a74154bffd7bfad742cf87dc3b051bc197

Release classification:

LOCAL RELEASE CANDIDATE

Production approved:

NO

---

## 10A. Content Engine 1.1.0 Governance Closeout

- Gate 3R Retroactive Review: PASS.
- Local Release Candidate 1.1.0: ACCEPTED; existing artifact and hashes remain unchanged.
- Runtime file count 33 → 32: CLOSED / INTENDED. Commit `30a7abd573884a30164e66e43a4a8cd84a44e03e` intentionally removed `templates/page-tin-tuc.html` with the legacy News Hub consumer.
- Packaging D-01 implementation: PASS. Repository-root denied-tree anchoring is enforced by the validator and TEST-22.
- The retained Packaging Policy v0.2 prose omitted the explicit anchor statement; this was a documentation gap, not an implementation failure.
- Governance Pack: Git-tracked by this closeout.

---

## 11. Current Owner Priority

Current focus is professional website administration and content operations.

Public-facing visual redesign is deferred.

Primary priorities:

P0 — Editorial Core

- Article Standard
- Category Governance
- Tag Governance
- Author identity
- Medical Reviewer
- Published / Updated / Reviewed dates
- Editorial workflow
- Featured Image standard
- Media governance
- WordPress Roles

P1 — Administration

- useful Posts admin columns
- practical filters
- content quality flags
- content inventory
- stale-content detection
- editorial dashboard

P1 — SEO / Discovery

- one SEO layer
- canonical URL
- XML sitemap
- metadata
- Open Graph
- Article structured data
- internal linking

Later:

- public UI / UX polish

Production work remains deferred until Development is Owner-approved.

---

## 12. Current Phase

Current phase:

CONTENT OPERATIONS 1.2

Goal:

Turn WordPress into a professional and modern medical publishing
and content-management environment.

Normal publishing operations should not require developer intervention.

Target editorial workflow:

Draft
→ Review
→ Scheduled or Published
→ Update
→ Periodic Review

### C1 Editorial Architecture

Status: PASS (Local Development)

Implemented and verified:

- Standard WordPress Posts remain the article model; no custom article Post Type or workflow status was added.
- Medical review uses the dedicated `drthai_review_medical_content` capability, granted to Administrators by default and assignable to individual users by an Administrator.
- Protected `drthai_medical_reviewer` and `drthai_reviewed_at` metadata record the authorized current user and a server-generated UTC timestamp.
- Unreviewed Draft and Pending Posts are blocked server-side from both Publish and Schedule transitions, including REST/Gutenberg requests; existing published Posts are grandfathered.
- Every Single Post receives one automatic disclaimer from `templates/single.html`; archive and search templates are unchanged.
- Newly created Posts default Comments to closed; existing Posts and comment settings were not mass-mutated.
- The 61-check C1 Local integration suite passed with synthetic data cleanup and existing-content integrity verification.
- Production remains DEFERRED and UNTOUCHED.

### C3 Media Governance

Status: PASS (Local Development)

Branch: `feature/content-ops-1.2-c3`

Implemented and verified:

- Native WordPress Attachments and the Media Library remain the media system.
- A valid Featured Image with meaningful, non-empty Attachment Alt Text is required when an unpublished Post enters Publish or Schedule.
- The media requirement is enforced together with C1 Medical Review for REST/Gutenberg and normal WordPress Post API transitions; existing Published Posts are grandfathered.
- `docs/MEDIA_STANDARD.md` documents filenames, Featured Images, Alt Text, body-image accessibility, formats, practical sizes based on current responsive image behavior, reuse, publishing rights, and replacement workflow.
- Identifiable patient media is prohibited and a concise native Media Library notice reminds upload-capable users of the rule; no automated detection claim or scanning system was introduced.
- The 52-check C3 Local integration suite and 61-check C1 suite passed with synthetic media cleanup and existing Post, Attachment, Featured Image, Alt Text, filename, and trusted SVG integrity verification.
- Packaging Validator passed with 33 runtime files, 13 runtime PHP files, and zero unclassified files.
- Production remains DEFERRED and UNTOUCHED.

### C2 WordPress Admin Experience

Status: PASS (Local Development)

Branch: `feature/content-ops-1.2-c2`

Implemented and verified:

- The native Posts List Table now adds Medical Review, Reviewed Date, Media, Updated Date, and Editorial Health columns while preserving useful WordPress columns and row actions.
- Read-only filters cover review status, actual recorded reviewers, media completeness, and Editorial Health; native Status, Category, Date, Author, and Search behavior remains available.
- Editorial Health is derived dynamically from C1/C3 review and media validity plus Excerpt and meaningful Category checks; no workflow status, health metadata, cache table, or bulk mutation path was added.
- Updated Date and Reviewed Date sorting use native query data and bounded SQL clauses without loading the full Posts collection into PHP.
- The 65-check C2 suite passed at a 120-Post synthetic scale with pagination, filter interoperability, security, and complete fixture-cleanup verification.
- C1 (61 checks) and C3 (52 checks) regression suites passed.
- Packaging Validator passed with 34 runtime files, 14 runtime PHP files, and zero unclassified files.
- Production remains DEFERRED and UNTOUCHED.

### C4 Content Lifecycle

Status: PASS (Local Development)

Branch: `feature/content-ops-1.2-c4`

Implemented and verified:

- Published and scheduled Posts receive a dynamically derived Current, Never Reviewed, Needs Review, or Updated Since Review state; Draft and Pending Posts remain Pre-publication. No lifecycle state is persisted.
- The review anchor is the later of valid C1 Reviewed At and the native publication time. Review Due uses one centrally configured 12-calendar-month interval, and a centrally capped 60-second grace excludes initial Publish/Schedule timestamp noise.
- The native Posts List is the content inventory. One Lifecycle column shows state and due context, while database-side Current, Needs Review, Never Reviewed, Updated Since Review, and Cần xử lý filters preserve pagination and interoperate with C2 and native controls.
- The existing C1 Medical Review area shows read-only lifecycle context. Normal WordPress editing and native Revisions remain the update/history workflow; only the existing authorized C1 re-review action records a new review.
- The 60-check C4 suite passed at a 180-Post synthetic scale, including state precedence, SQL-bounded filters, pagination, display, C1 reset path, fixture cleanup, and existing-content integrity.
- C1, C2, and C3 regression suites passed. Content Engine HTTP and Booking protected-boundary checks passed.
- Packaging Validator passed with 35 runtime files, 15 runtime PHP files, and zero unclassified files.
- Production remains DEFERRED and UNTOUCHED.

### C5 SEO & Discovery

Status: PASS (Local Development)

Branch: `feature/content-ops-1.2-c5`

Implemented and verified:

- Yoast SEO Free is the single active SEO layer. The Local plugin slug is `wordpress-seo`, tested at version 28.3 on WordPress 7.0.4 and PHP 8.3.33.
- Official plugin metadata requires WordPress 6.9 and PHP 7.4 and reports Tested Up To WordPress 7.1. Local compatibility testing passed without a version-lag caveat; Production Predeploy must re-check current supported versions.
- Yoast owns SEO titles, Meta Descriptions, indexation metadata, canonical behavior, XML sitemap, Open Graph, social metadata, and the Article/WebPage Schema graph. A minimal conditional theme shim removes WordPress 7.0's duplicate block-theme title renderer only while Yoast is active.
- Local indexing is disabled with `blog_public=0`. Yoast appropriately emits noindex output and suppresses Local frontend canonicals; Production canonical URLs and indexing must be verified only during approved Production work.
- `/sitemap_index.xml` returns valid XML with Post, Page, Category, and Tag coverage. `/wp-sitemap.xml` redirects to Yoast, so no independent second sitemap layer remains.
- `docs/SEO_STANDARD.md` documents the editor workflow, Featured Image/social relationship, taxonomy and internal-linking practices, AI restrictions, and Production Search Console readiness. No external account or verification property was connected.
- The 67-check C5 suite passed with custom title/description, Open Graph, Schema, sitemap dynamics, C1/C3/C4 integration, and complete synthetic fixture cleanup. C1, C2, C3, and C4 regressions passed.
- Content Engine HTTP and Booking boundary checks passed. Packaging Validator passed with 35 runtime files, 15 runtime PHP files, and zero unclassified files.
- Production remains DEFERRED and UNTOUCHED.

---

## 13. Do Not Build Without Real Need

Do not introduce without demonstrated requirements:

- Headless WordPress
- custom CMS
- Elasticsearch
- complex workflow engine
- excessive custom roles
- AI auto-publishing
- custom SEO engine
- microservices
- multilingual architecture
- patient portal
- EMR/HIS
- custom analytics platform

Prefer native WordPress capabilities whenever practical.

---

## 14. UI-UX 2.0 — Public Experience Polish

Status:

PASS / OWNER ACCEPTANCE PENDING

Verified locally on 2026-08-18:

- actual rendered visual audit covered Home, News, Single Post, Category, Tag, Search, 404, About, Expertise, Contact, and Booking;
- shared typography, article reading, discovery cards, search, CTA, form, and footer presentation were polished;
- responsive checks passed at 320, 390, 768, and 1440 pixel viewport widths with no page-level horizontal overflow;
- native mobile navigation open/close presentation, keyboard focus, and practical touch targets passed;
- C1–C5 targeted regression, protected Booking boundary, SEO runtime, and Content Engine routing passed;
- Packaging Policy and Validator passed with zero unclassified files;
- Production remained deferred and untouched.

Durable public experience rules are documented in `docs/PUBLIC_EXPERIENCE_STANDARD.md`.

---

## 15. Development Acceptance 2.0

Status:

PASS / EXTERNAL REVIEW REQUIRED

Verified on 2026-08-19:

- accepted runtime candidate: `fe3a670734093982afc87ca9393002f6d5e58160`;
- accepted lineage, C1–C5 regression, Content Engine routing, Booking boundary, UI/UX smoke, accessibility sanity, controlled Local restart, data persistence, security sanity, Packaging Policy, Packaging Validator, and zero-unclassified gates passed;
- committed Gate evidence is documented in `docs/DEVELOPMENT_ACCEPTANCE_2.0.md`;
- external review and Owner Development Approval remain pending;
- Production Preparation is NOT AUTHORIZED and Production remains DEFERRED.

---

## 16. Production Status

Production work is currently DEFERRED.

Deferred items include:

- Production Core parity verification
- Production deployment
- Domain and HTTPS completion
- Production backup validation
- Restore test
- Security hardening
- Monitoring
- Production Readiness Review

No Production mutation without explicit Owner approval.

---

## 17. Next Mission

Next Mission:

EXTERNAL_REVIEW

Submit Development Acceptance 2.0 evidence for mandatory external review. Do not begin Production Preparation.

---

## 18. Maintenance Rules

This file represents factual current project state.

Codex MAY update factual sections after completing an Owner-approved Mission.

Codex MUST NOT silently change:

- Owner priorities
- architecture decisions
- Production authorization
- security boundaries
- project scope

If repository evidence conflicts with this file:

report the discrepancy and use newer verified repository evidence.

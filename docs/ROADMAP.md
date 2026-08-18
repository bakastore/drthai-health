# DrThai Health — Development Roadmap

Status: OWNER-CONTROLLED
Codex permission: READ ONLY unless Owner explicitly approves an update

---

## 1. Product Goal

Build DrThai Health into a professional WordPress medical publishing platform.

The website should be:

- easy to publish
- easy to administer
- maintainable
- secure
- SEO-friendly
- recoverable
- suitable for long-term medical publishing

It is not:

- EMR
- HIS
- patient portal
- patient medical record system

No real patient data should be stored.

---

## 2. Completed Foundation

### Phase 1 — WordPress and Theme Foundation

Status:

COMPLETED BASELINE

Includes:

- WordPress environment
- custom DrThai Health block theme
- primary site Pages
- Booking request flow
- Local development environment
- Production baseline environment

---

## 3. Phase 2 — Content Engine 1.1.0

Status:

LOCAL DEVELOPMENT COMPLETE

Completed:

- repository restructuring
- Local WordPress Core alignment
- Packaging Policy v0.2
- deterministic packaging validator
- Local content routing migration
- native WordPress Posts Page
- native Single Posts
- Category archives
- Tag archives
- Search
- 404
- development content seed
- runtime content bootstrap removal
- legacy News Hub removal
- Local Release Candidate 1.1.0

Production deployment remains deferred.

---

## 4. Phase 3 — Content Operations 1.2

Status:

NEXT

Objective:

Transform WordPress from a technically functional website into a
professional medical publishing and administration system.

### C1 — Editorial Architecture

Priority:

P0

Deliver:

- Article Standard
- Category Governance
- Tag Governance
- Author model
- Medical Reviewer model
- Published date
- Updated date
- Reviewed date
- Draft → Review → Scheduled / Published workflow
- Comments policy

Definition of Done:

An editor can prepare and publish a professionally structured medical
article without developer assistance.

### C2 — WordPress Admin Experience

Priority:

P0 / P1

Deliver:

- useful Posts list columns
- practical filters
- author/category/status/date visibility
- content quality indicators
- simplified editorial workflow

Definition of Done:

An editor can efficiently find, filter and manage hundreds of articles.

### C3 — Media Governance

Priority:

P0

Deliver:

- image naming policy
- Featured Image standard
- Alt Text requirements
- image format guidance
- practical image-size guidance
- no identifiable patient data
- consistent Media Library workflow

Definition of Done:

Media uploaded for articles follows a consistent publishing standard.

### C4 — Content Lifecycle

Priority:

P1

Deliver:

- content inventory
- reviewed-date metadata
- stale-content identification
- revision workflow
- article update workflow
- periodic review workflow

Definition of Done:

The Owner can identify which published articles require review or update.

### C5 — SEO / Discovery

Priority:

P1

Deliver:

- one SEO layer only
- canonical URLs
- XML sitemap
- SEO title
- meta description
- Open Graph
- Article structured data
- internal-linking workflow
- Search Console readiness

Do not build a custom SEO engine.

Definition of Done:

Published content exposes the metadata required for modern search and
social discovery.

---

## 5. Phase 4 — Public UI / UX Polish

Status:

DEFERRED BY OWNER

Begin after Content Operations becomes functionally mature.

Possible scope:

- typography
- article reading experience
- archive cards
- spacing
- hero refinement
- mobile navigation
- accessibility
- visual hierarchy
- CTA refinement
- public content presentation

UI work must not block Content Operations.

---

## 6. Phase 5 — Production Preparation

Status:

DEFERRED

Begin only after the Owner considers Development complete and stable.

Required before deployment:

1. Verify Production runtime WordPress Core.
2. Confirm Local and Production compatibility.
3. Create fresh Production backup.
4. Verify backup integrity.
5. Prepare rollback.
6. Verify release artifact SHA256.
7. Verify migration script SHA256.
8. Perform predeploy security review.
9. Perform controlled Production deployment.
10. Verify Production release.

Production mutations require explicit Owner approval.

---

## 7. Phase 6 — Production Readiness

After application deployment:

- domain
- DNS
- HTTPS
- TLS renewal
- security hardening
- Two-Factor Authentication
- least privilege
- automated backup
- external backup copy
- restore test
- website monitoring
- disk monitoring
- container monitoring
- TLS monitoring
- backup monitoring
- reboot test
- Build documentation
- Operations Runbook
- Backup Runbook
- Restore Runbook
- Incident Runbook

---

## 8. Go-Live Definition

Production Go-Live requires:

- Development Owner-approved
- content publishing works
- Media upload works
- domain works
- HTTPS works
- only intended public services exposed
- automated backup works
- external backup exists
- restore tested successfully
- monitoring active
- security review passes
- reboot test passes
- operational documentation exists
- no real patient data stored

---

## 9. Current Execution Order

Current priority order:

CONTENT OPERATIONS 1.2
→ Content-management maturity
→ SEO / Discovery readiness
→ UI / UX final polish
→ Owner Development approval
→ Production preparation
→ Production deployment
→ Backup / Restore / Monitoring / Security
→ Go-Live

---

## 10. Engineering Principle

Prefer:

WordPress Native
→ Simple
→ Maintainable
→ Testable
→ Reversible

Avoid custom systems unless native WordPress is demonstrably insufficient.

Do not optimize for hypothetical future requirements.

Build for the real publishing workflow first.

---

## 11. Governance

This roadmap is OWNER-CONTROLLED.

Codex MUST NOT modify it during normal:

- implementation
- testing
- debugging
- self-repair
- refactoring

Roadmap changes require:

1. explicit Owner decision
2. Git-visible update

If repository state differs from this roadmap:

do not silently rewrite the roadmap.

Report the discrepancy.

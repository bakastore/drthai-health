# DrThai Health — Development Acceptance 2.0

Status: PASS — EXTERNAL REVIEW REQUIRED

## Identity

- Gate: `DEVELOPMENT-ACCEPTANCE-2.0 / FINAL LOCAL DEVELOPMENT GATE`
- Executed: 2026-08-19 09:41–09:52 Asia/Bangkok
- Runtime candidate SHA: `fe3a670734093982afc87ca9393002f6d5e58160`
- Verification branch: `gate/development-acceptance-2.0`
- Local URL: `http://localhost:8888`
- Environment: WSL2 Linux; Node 20.19.6; npm 10.8.2; Docker 29.1.3; wp-env 11.7.0; WordPress 7.0.4; PHP 8.3.33
- Active theme: `drthai-health` 1.1.0
- Active plugin: Yoast SEO Free (`wordpress-seo`) 28.3 only

## Lineage

Current runtime candidate contains every required accepted commit:

- `c030b67d53c3ca36f4c322e06d57539cf5f96ee1` — PASS
- `3e60e3058b5f6eba7ea3f75bb5c53105d5422de1` — PASS
- `3475438a0ec75b8ca7ee4bbab43a5141e475ef82` — PASS
- `7877ebd3f6d7256f387f4c60fe5e2001647e7024` — PASS
- `7f4b66e16846668c21b9d39923beb7827e55a434` — PASS
- `32426e57648cd9f4a258f5392ad3300d7a19a11d` — PASS
- `fe3a670734093982afc87ca9393002f6d5e58160` — PASS

The runtime branch began clean and matched its remote feature HEAD.

## SSOT reconciliation

- `PROJECT_STATE.md` correctly recorded completed C1–C5 and UI-UX 2.0 evidence, Local noindex, and deferred Production. Its older Current Priority/Current Phase prose still describes Content Operations and deferred UI work; this is stale wording superseded by newer sections and verified repository evidence.
- Owner-controlled `ROADMAP.md` still labels Content Operations as next and UI polish as deferred. This is stale phase-status wording; its execution order and requirement for Owner Development approval before Production remain consistent. It was not modified.
- Owner-controlled `ACCEPTANCE_GATES.md` remains unchanged and its mandatory Content Engine, Booking, routing, data-integrity, and packaging assertions were applied.
- No material governance contradiction requiring a runtime or protected-boundary decision was found.

## Runtime inventory

- Packaging Policy: v0.2 — PASS
- Packaging Policy tests: 21 PASS, 1 allowed filesystem-capability skip
- Packaging Validator: PASS
- Runtime files: 35
- Runtime PHP files: 15
- Unclassified files: 0
- Denied repository-root trees, including `docs/**` and `scripts/**`: excluded
- Runtime manifest SHA256: `bf1becbdb505159aefa0b90fa93ae99b7c1aa4e6cdebbab916ac0b0d0111e73e`

## Functional verification

### Public routing and Content Engine

| Surface | Result |
| --- | --- |
| Home | HTTP 200 |
| News / Posts Page | HTTP 200; published Post link present; no legacy shortcode residue |
| Single Post | HTTP 200; resolved title present |
| Category | HTTP 200 |
| Tag | HTTP 200 |
| Search | HTTP 200; expected result present |
| About | HTTP 200 |
| Expertise / Services | HTTP 200 |
| Contact | HTTP 200 |
| Booking | HTTP 200; required form identifiers present |
| Yoast sitemap | HTTP 200; valid XML |
| Known missing URL | HTTP 404 |

Native Posts, Posts Page, archives, Search, and 404 remain active. Runtime `drthai_news_hub` and automatic content bootstrap remain absent.

### Booking

- Protected Booking block SHA256: `4d31eed75dc40a8b72bd5b3e7feaefe7e74168a3a3b77f1a04d5dbd4a342f2d2` — PASS
- Accepted Booking identifier anchor: `c26873063ca8c8326832370935454c7f56aadf194ca45caf547536fc04a52309`
- `functions.php` Git blob exactly matches the accepted runtime candidate; Booking runtime diff: none
- Existing private Booking record count and IDs persisted: 5 records, IDs 15, 16, 17, 18, 27
- Callback transient residue: 0
- No form submission or Booking mutation was performed by this Gate

### Accepted regression suites

- C1 Editorial Architecture: 61/61 PASS
- C2 Admin Experience: 65/65 PASS
- C3 Media Governance: 52/52 PASS
- C4 Content Lifecycle: 60/60 PASS using the documented time-corrected command below
- C5 SEO / Discovery: 67/67 PASS

The unmodified C4 command first produced one false negative at `UPDATED SINCE REVIEW displays correctly`. The test re-reviews the fixture using current time and then hard-codes its modified time to `2026-08-18 23:59:59`; after that date, the modified timestamp is no longer after the review anchor. No repository file was changed. An in-memory-only rerun replaced that expired fixture timestamp with current UTC time plus 120 seconds; all 60 assertions and cleanup/integrity checks passed. This is a time-sensitive test-fixture limitation, not evidence of a lifecycle runtime defect.

### SEO

- Yoast remains the sole active SEO layer
- Exactly one HTML title and one Article Schema node were emitted on the representative Single Post
- One Yoast JSON-LD graph and `og:type=article` were present
- Local `noindex` remained active with `blog_public=0`
- `/sitemap_index.xml` parsed as XML; Core `/wp-sitemap.xml` redirected to the Yoast index

### UI/UX and accessibility sanity

- Rendered Home, News, Single, Booking, Search, and 404 at 390, 768, and 1440 pixel widths
- No page-level horizontal overflow, clipped navigation, overlap, or missing image Alt attribute was found
- Each representative surface had one H1
- Mobile menu opened and closed; all five links remained visible with approximately 59.5-pixel target height
- Article, forms, focus styling, reduced-motion rules, and 404 presentation remained intact
- Chrome CDP reported no console/resource error on valid pages; the only 404 log event was the intentionally missing main document
- This is a practical WCAG 2.2 AA-oriented sanity check, not formal certification

## Restart verification

The environment was initially stopped, so it was started to collect the pre-test runtime facts. After verification, exactly one controlled restart cycle was performed:

- `wp-env stop`: PASS
- `wp-env start`: PASS
- WordPress recovery: PASS
- Database persistence: PASS
- Active theme persistence: PASS
- Yoast persistence: PASS
- Routing persistence: PASS
- Local noindex persistence: PASS
- Post-restart HTTP matrix: PASS

No destroy, clean, reset, database recreation, or volume deletion was used.

## Data integrity

Before tests, after tests, and after restart, the verified counts remained:

- Posts: 2 Published and 2 auto-drafts; no Draft, Pending, Future, Private, or Trash Posts
- Pages: 9 Published and 1 Draft
- Attachments: 1
- Categories: 8
- Tags: 3
- Users: 1
- Booking records: 5
- Medical Reviewer metadata rows: 0
- Reviewed At metadata rows: 0
- Persisted lifecycle metadata rows: 0
- Yoast Post Meta rows: 0

Reference Post 24 content SHA256 remained `325f2ceddc764f68774f667f331ac147187b19de342f111a36718fc4b3e06851`. All test suites cleaned their synthetic fixtures. No existing content was deleted, recreated, or mass-mutated; no review, lifecycle, SEO, Booking, or patient data was introduced.

## Security checks

- Tracked `.env` files: 0
- Tracked private-key markers: 0
- Tracked database dumps: 0
- Common credential-assignment patterns: 0
- SEO verification tokens: 0
- Unrestricted SVG upload: absent and covered by C3
- New privileged/admin bypass: absent and covered by C1–C5 security assertions
- Patient-record marker scan: 0; test fixtures were synthetic and cleaned
- The sole runtime `drthai.blog` URL is the public Theme URI metadata, not a credential or environment-dependent runtime route

## Raw evidence

- Local evidence directory: `/tmp/drthai-development-acceptance-2.0/`
- Screenshots: 20 files stored outside the repository
- Core evidence index: `/tmp/drthai-development-acceptance-2.0/CORE_EVIDENCE_SHA256SUMS`
- Core evidence index SHA256: `d95335b2cbb6425931f2512c956069cca84b7cdc2646eb4ed320dd5612b90c88`
- No screenshots, browser dumps, database dumps, or large logs are committed

## Known limitations

- The tracked C4 test contains one expired fixed timestamp; the original false-negative log and the passing in-memory time-corrected rerun are both retained for external review.
- Packaging TEST-08 was skipped because the fixture filesystem could create neither a FIFO nor an AF_UNIX socket. Policy still rejects special files, and the live Validator special-file gate passed.
- Automated axe/pa11y was not installed; accessibility evidence is practical browser, DOM, focus, form-label, contrast, Alt, touch-target, and overflow verification.
- Production domain, HTTPS, canonical/indexing behavior, Search Console, backup/restore, hardening, and monitoring remain deferred.

## Review state

`EXTERNAL_REVIEW_REQUIRED`

`OWNER_DEVELOPMENT_APPROVAL_PENDING`

`PRODUCTION_PREPARATION_NOT_AUTHORIZED`

Production remains deferred and untouched. No release ZIP, merge, or tag was created.

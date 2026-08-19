# DrThai Health Content Lifecycle

Status: LOCAL DEVELOPMENT

## Purpose

The native **Posts → All Posts** screen is the content inventory. Its Lifecycle column and filter help editors find published or scheduled medical articles that need attention without creating a separate workflow system or stored lifecycle status.

## Lifecycle states

- **Current** — valid Medical Review, still within the 12-calendar-month review interval, and no update later than the review anchor plus the small initial-publication grace.
- **Never Reviewed** — published or scheduled content without both a valid reviewer and valid Reviewed At value.
- **Needs Review** — valid Medical Review whose due date has arrived, with no higher-priority post-review update signal.
- **Updated Since Review** — the native modified time is more than 60 seconds after the review anchor. This is an editorial signal, not a claim that the medical content is wrong.
- **Pre-publication** — Draft or Pending content. Periodic lifecycle review does not apply yet; normal readiness and publishing gates still apply.

The review anchor is the later of the server-recorded Medical Review time and the effective native publication time. Review Due is that anchor plus 12 calendar months. Calculations use UTC; displayed dates use the WordPress site timezone. Lifecycle is calculated from native dates and protected C1 review metadata and is never stored in Post Meta.

## Using the content inventory

1. Open **Posts → All Posts**.
2. Read the existing Medical Review, Reviewed, Media, Updated, and Editorial Health columns together with **Lifecycle**.
3. Use **Tất cả vòng đời** to select Current, Needs Review, Never Reviewed, Updated Since Review, or **Cần xử lý**. Cần xử lý combines all three attention states.
4. Combine the Lifecycle filter with Reviewer, Media, Editorial Health, Category, Status, or Search when useful.
5. Open an article to see the read-only Lifecycle and Review Due context in the existing Medical Review area.

The controls only find content. Listing, filtering, or opening a Post does not alter its content, dates, reviewer, or review timestamp.

## Editorial workflows

### Current article

Leave the article published and review it by the displayed due date.

### Needs Review

Open the article and check its content. If no edit is needed, an authorized reviewer uses **Mark as Medically Reviewed**. If an edit is needed, update and preview it first, then perform Medical Re-review when medically appropriate.

### Never Reviewed

Check and complete the article, then have an authorized reviewer perform Medical Review. C4 does not assign a reviewer, invent a date, or unpublish legacy content.

### Updated Since Review

Inspect the native revision history and decide whether the change needs medical re-review. The signal does not automatically invalidate the existing review. When appropriate, use the existing C1 Medical Review action; the new reviewer and server time become the source of truth and the lifecycle returns to Current when its other conditions pass.

## Updating an article

Find the article → review Lifecycle and Editorial Health → edit in the normal WordPress editor → preview → update → inspect native Revisions when needed → perform Medical Re-review when medically appropriate → confirm Lifecycle is Current → verify the public article.

WordPress Revisions remain the only revision system. C4 does not compare medical meaning, classify whether a change is material, auto-review, auto-unpublish, clear review metadata, send reminders, or create a cron/queue/database lifecycle engine.

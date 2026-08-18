# DrThai Health Editorial Standard

Status: LOCAL DEVELOPMENT

## Article standard

A professionally prepared medical Post should include a clear Title, structured Content, one native WordPress Author, an appropriate Category, Featured Image, Excerpt, Medical Reviewer, Reviewed Date and the automatic Medical Disclaimer.

Medical Reviewer and Reviewed Date are hard requirements for publishing or scheduling. The remaining items are editorial standards in C1 and should be checked before review.

## Workflow

1. Prepare the article as a Draft.
2. Move it to Pending Review when editorial preparation is complete.
3. An authorized reviewer checks the medical content and uses **Mark as Medically Reviewed** in the Post editor.
4. A user with normal WordPress publishing permission may then Publish or Schedule the Post.

Medical Review records the authorized WordPress user and a server-generated UTC timestamp. Editors do not type reviewer names or reviewed dates. Re-review replaces both values with the current authorized reviewer and current server time.

AI-assisted medical content may remain a Draft, must never be auto-published and follows the same Medical Review requirement.

## Author model

Use the native WordPress Post Author. Each Post has one primary Author; C1 does not provide multi-author records or a separate author entity.

## Category governance

Categories are intentionally limited primary publishing sections. Create a Category only for a durable section, not for one article. Avoid near-duplicates and prefer an existing suitable Category.

## Tag governance

Tags support useful discovery concepts such as diseases, symptoms, procedures and clinically relevant subjects. Reuse appropriate Tags, avoid duplicate or near-duplicate Tags and never use Tags for keyword stuffing.

## Comments policy

New medical Posts default to Comments Closed. C1 does not mass-change historical Posts or comments.

## Medical Disclaimer

Every public Single Post automatically displays the theme Medical Disclaimer after article content. Editors must not insert a duplicate disclaimer into Post content. The current wording is `LOCAL_DEVELOPMENT_PROVISIONAL` and requires final doctor/Owner approval before Production.

## Media

Follow `docs/MEDIA_STANDARD.md` for filename, Featured Image, Alt Text, patient-data, format, size, reuse, and rights guidance. A valid Featured Image with meaningful Alt Text is required before a Draft or Pending Post can be Published or Scheduled.

## Managing Articles in WordPress

1. Open **Posts → All Posts**. Use the native Title, Author, Categories, and Date information together with the Medical Review, Reviewed, Media, Updated, and Editorial Health columns.
2. Read **Editorial Health** first. `OK` identifies complete Published/Scheduled content, `READY` identifies a complete unpublished Post, and `NEEDS ATTENTION` lists the specific items to fix.
3. Use **Needs Attention** to find incomplete articles, then use **Chưa rà soát**, **Thiếu Featured Image**, or **Thiếu Alt Text** to narrow the work.
4. Use the Reviewer filter to find articles reviewed by a particular person. Continue using WordPress native Category, Status, Author, Date, and Search controls where appropriate.
5. Open the Post and correct the reported items through the normal editor, Media Library, and Medical Review action. C2 list columns and filters are read-only.
6. Return to **All Posts**, repeat the relevant filter, and confirm the row now shows the expected `READY` or `OK` state.

## Existing-content grandfather rule

Existing published Posts remain published even if they predate Medical Review metadata. C1 does not fabricate historical reviewers or reviewed dates, unpublish legacy content, or invalidate review metadata after later edits. Content lifecycle and material-change policy belong to C4.

See `docs/CONTENT_LIFECYCLE.md` for the 12-month periodic review model, lifecycle inventory filters, update and native Revision workflow, and Medical Re-review procedure.

See `docs/SEO_STANDARD.md` for the Yoast-owned metadata layer, SEO editorial workflow, internal linking guidance, and Production Search Console readiness checklist.

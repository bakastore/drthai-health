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

## Existing-content grandfather rule

Existing published Posts remain published even if they predate Medical Review metadata. C1 does not fabricate historical reviewers or reviewed dates, unpublish legacy content, or invalidate review metadata after later edits. Content lifecycle and material-change policy belong to C4.

# DrThai Health Media Standard

Status: LOCAL DEVELOPMENT

Use the native WordPress Media Library for article images. Never upload real identifiable patient information.

## Before upload

1. Confirm that DrThai Health has the right to publish the image. Record the source or required attribution in the normal editorial record when relevant; never invent either.
2. Check the entire image or document for patient names, patient IDs, medical-record numbers, phone numbers, addresses, identifiable labels, screenshots, or unredacted examination/report details. Do not upload it if any patient can be identified.
3. Search the Media Library and reuse a suitable existing Attachment instead of uploading a duplicate.
4. Prepare a descriptive filename in the form `descriptive-lowercase-slug.ext`. Use lowercase and hyphens where practical, for example `noi-soi-da-day-minh-hoa.webp` or `dau-bung-vung-thuong-vi.webp`. Avoid spaces, `IMG_1234`, random names, and all patient identifiers. WordPress will apply its native filename sanitization.

Do not rename existing uploaded files in bulk because that can break media URLs.

## Featured Image

Every newly Published or Scheduled medical Post requires one valid Featured Image with meaningful, non-empty WordPress Alt Text. WordPress blocks publishing or scheduling until both are present.

The current Single Post template presents Featured Images at 16:9. News, archive, and search cards use 16:10. Choose an image whose important subject remains clear in both crops, preserve its aspect ratio, and do not stretch it manually.

## Alt Text

Describe the useful visual information or purpose concisely and in context. Do not use the filename, keyword stuffing, invented details, or phrases such as “image of” merely to satisfy the check.

For images inside article content:

- meaningful image: provide descriptive Alt Text;
- purely decorative image: intentionally use empty Alt Text.

C3 hard-enforces Alt Text only for the Featured Image. Editors remain responsible for body-image accessibility.

## Format and size

- Prefer WebP for suitable web photographs and illustrations when practical.
- Use JPEG/JPG when photographic compatibility is needed.
- Use PNG only when transparency or lossless raster output is genuinely useful.
- Do not upload SVG files through the Media Library. Existing trusted theme SVG assets are developer-managed and remain unchanged.

Upload an original large enough for its intended presentation without being unnecessarily huge. The current site uses WordPress responsive sizes at 150px, 300px, 768px, 1024px, 1536px, and 2048px, and WordPress applies its standard 2560px large-image threshold. A well-composed image around 1600–2048px on its long edge is normally practical for a Featured Image; use a larger original only when the intended display genuinely needs it. Optimize before upload where practical and let WordPress provide responsive variants.

## Article media workflow

Prepare image → verify publishing rights → verify no patient-identifying data → use a descriptive filename → search the Media Library → upload only when needed → add Alt Text → add a useful Caption or Description when relevant → assign as Featured Image or insert into content → preview the relevant crops → complete the normal Medical Review and Publish/Schedule workflow.

## Replacement and updates

When an image must change, first decide whether the existing Attachment should be reused or whether a new file is required. Update Alt Text and captions to match the new visual meaning. Preview the Post after replacement. Do not overwrite or delete shared media until every place using it has been checked, and do not treat a media change as a substitute for a new Medical Review when clinical meaning has materially changed.

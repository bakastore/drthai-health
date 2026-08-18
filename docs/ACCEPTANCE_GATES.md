# Gate 2 — Content Engine 1.1.0 Acceptance Criteria

These are the Owner-approved mandatory assertions for Gate 2. Supplementary tests may be added, but these assertions may not be weakened.

## HTTP and content matrix

- Root: HTTP 200; the static Front Page remains correct.
- News: `/tin-tuc/` returns HTTP 200.
- News content: `/tin-tuc/` contains at least one Published Post link.
- Legacy: the `/tin-tuc/` response contains no shortcode residue.
- Single: `/tin-tuc/<existing-post-slug>/` returns HTTP 200 and contains the exact existing Post Title.
- Category: `/chuyen-muc/<existing-category-slug>/` returns HTTP 200 and the correct archive.
- Tag: `/tu-khoa/<existing-tag-slug>/` returns HTTP 200 and the correct archive.
- Search: a known search query returns HTTP 200 and the expected matching post/content.
- 404: a known nonexistent URL returns HTTP 404 and renders the 404 template.
- Booking: `/dat-lich/` returns HTTP 200 and contains the expected form identifiers.

Existing content must be resolved dynamically for these tests.

## Source assertions

- The runtime set contains no active dependency on `drthai_news_hub`.
- No runtime content bootstrap automatically creates site content.
- The development seed exists and is excluded from the runtime artifact.
- The migration script remains excluded from the runtime artifact.
- `docs/**` remains excluded from the runtime artifact.
- `scripts/**` remains excluded from the runtime artifact.

## Booking boundary

- `BOOKING_BLOCK_SHA256` must equal `4d31eed75dc40a8b72bd5b3e7feaefe7e74168a3a3b77f1a04d5dbd4a342f2d2`.
- `BOOKING_IDENTIFIER_SHA256` must equal `c26873063ca8c8326832370935454c7f56aadf194ca45caf547536fc04a52309`.
- The previous legacy-content hash is not immutable after T5.
- Changes in the former legacy block are limited to the Owner-approved removal or migration of the News Hub runtime implementation and runtime content bootstrap.

## Data integrity

- The existing reference Post content SHA256 remains `325f2ceddc764f68774f667f331ac147187b19de342f111a36718fc4b3e06851` using the established hashing procedure.
- No existing Page or Post is silently deleted or recreated.
- Taxonomy additions are reported separately.

## Routing integrity

- `show_on_front=page`
- `page_on_front=6`
- `page_for_posts=9`
- `permalink_structure=/tin-tuc/%postname%/`
- `category_base=chuyen-muc`
- `tag_base=tu-khoa`

## Packaging

- Packaging Validator: PASS.
- Unclassified files: 0.
- `docs/**`: DENIED.
- `scripts/**`: DENIED.
- Runtime PHP surface: report count and list.
- Do not build a Final Production ZIP during Gate 2.

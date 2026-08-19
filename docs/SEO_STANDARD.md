# DrThai Health SEO Standard

Status: LOCAL DEVELOPMENT

## One SEO layer

Yoast SEO Free is the single SEO and discovery layer. Its official WordPress.org slug is `wordpress-seo`; Local verification uses version 28.3. Do not install another SEO, sitemap, Open Graph, or Schema plugin, and do not reproduce these features in the theme.

The plugin is installed in Local WordPress, not vendored into this theme repository or included in its runtime ZIP. A fresh Local environment can install the verified dependency from the official repository with:

```bash
wp plugin install wordpress-seo --version=28.3 --activate
```

Re-check the current supported WordPress, PHP, and plugin versions before Production deployment rather than treating this Local command as a permanent Production version pin.

## Editor workflow

Draft article → complete C1 Medical Review → complete C3 Featured Image and Alt Text requirements → set a useful SEO Title when needed → write a useful Meta Description → inspect canonical settings only for a genuine special case → verify the Featured Image/social preview → add useful internal links → Preview → Publish or Schedule through the existing C1/C3 gates → use the C4 review/update workflow later.

Yoast SEO fields are available in the normal WordPress Post and Page editor. A custom SEO Title or Meta Description is an editorial quality improvement, not an additional publishing gate. When the default title is already clear and accurate, it may remain. Descriptions should summarize the page honestly and concisely; never invent evidence, outcomes, or qualifications.

The Featured Image is normally the social-sharing image. Follow `docs/MEDIA_STANDARD.md`, verify rights and Alt Text, and check the social preview. Do not fabricate or auto-generate a social image merely to fill metadata.

## Ownership and discovery

- **Canonical URLs:** Yoast owns canonical output. Do not add a second canonical tag or hard-code a domain. Local is intentionally noindex, so Yoast may suppress frontend canonical tags there; Production canonical behavior must be rechecked after the final domain and indexing decision.
- **XML sitemap:** the primary endpoint is `/sitemap_index.xml`. The former Core `/wp-sitemap.xml` endpoint redirects to the Yoast index, leaving one sitemap authority.
- **Open Graph and social metadata:** Yoast owns the output and uses the Featured Image where applicable.
- **Article structured data:** Yoast’s Schema graph owns Article and WebPage/site representation. Do not add a second JSON-LD Article graph or invent medical Schema types.
- **Categories and Tags:** keep using the governed native taxonomies. Categories and Tags remain discoverable and represented in the sitemap under the current configuration. Do not create near-duplicates or use Tags for keyword stuffing.
- **Search and Posts Page:** retain native WordPress Search, `/tin-tuc/`, archives, and Single Posts. C5 adds no search service or recommendation engine.

Site representation is intentionally limited to facts already present in WordPress. No external profiles, qualifications, organization identity, logo ownership, or verification tokens were invented or connected.

## Internal linking

Before publishing or updating:

1. Search for relevant existing DrThai Health articles.
2. Add a contextual link only when it helps the reader.
3. Use descriptive anchor text that explains the destination.
4. Verify that the destination still exists and is appropriate.
5. Avoid generic repeated anchors, arbitrary link quotas, keyword stuffing, and links added only for ranking.

Use native WordPress linking tools. Yoast Premium is not required, and C5 does not automatically suggest or insert links.

## AI SEO policy

Do not use Yoast AI or another external AI account to auto-generate titles, descriptions, links, or medical rewrites. Do not auto-publish. If a user explicitly requests an AI suggestion in a future approved task, it remains a draft requiring human and medical judgment.

## Local indexing safety

Local Development uses `blog_public=0`. Representative pages emit noindex metadata, and no Local sitemap is submitted externally. This restriction is separate from technical SEO readiness. Do not enable indexing merely to preview Production behavior.

## Production and Search Console readiness

Search Console is not connected in Local Development. During a separately approved Production mission:

1. Confirm the final Production domain and HTTPS.
2. Re-check supported WordPress, PHP, and Yoast versions.
3. Verify Production canonical URLs use the final HTTPS domain.
4. Enable Production indexing intentionally only after approval.
5. Verify robots and page-level indexation behavior.
6. Confirm the public sitemap is reachable without authentication and contains only Production URLs.
7. Verify Search Console property ownership using an Owner-approved method.
8. Submit the Production sitemap.
9. Inspect representative URLs after Go-Live.
10. Monitor sitemap, crawl, and indexing errors.

Do not add a Local verification token, connect an external account, or submit a Local URL.

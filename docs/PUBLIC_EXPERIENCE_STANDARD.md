# DrThai Health — Public Experience Standard

Status: UI-UX 2.0 baseline

## Visual identity

- Present a calm, professional medical publishing experience using the established cream, green, white, sage, and restrained gold palette.
- Build trust through clear information, consistent authorship, whitespace, and readable medical content. Never use invented badges, outcomes, statistics, or testimonials.
- Reuse `theme.json`, native blocks, and shared theme styles before adding route-specific presentation rules.

## Typography and reading

- Preserve semantic heading order and use the display face for hierarchy, not decoration.
- Keep long-form article text near a 60–80 character measure, with comfortable line height and paragraph spacing.
- Keep lists, blockquotes, captions, links, images, and tables readable. Long words and URLs must wrap; wide tables may scroll locally without causing page-level overflow.

## Responsive layout

- Design mobile-first and verify narrow mobile, mobile, tablet, and desktop widths.
- Shared content, cards, forms, media, and footer regions must reflow without clipping or horizontal page scroll.
- Keep mobile heroes compact enough that identity and the primary action remain understandable without excessive empty space.

## Navigation

- Retain native WordPress Navigation block behavior and the established primary destinations.
- Desktop navigation must provide clear hover, current-page, and keyboard-focus states.
- Mobile navigation must open and close reliably, show every primary link without clipping, and provide practical touch targets of at least 44 by 44 pixels.

## Articles and discovery

- Single articles are the strongest reading surface: clear title and metadata, responsive Featured Image, constrained content, visible medical disclaimer, and usable previous/next navigation.
- News, category, tag, and search results share card structure, image treatment, metadata hierarchy, excerpt rhythm, and empty-state language.
- Public cards contain only reader-facing context; editorial workflow, lifecycle, and SEO administration data remain private to WordPress Admin.

## Calls to action and forms

- Use booking or contact as the contextual primary action without urgency manipulation or implying that a request is a confirmed appointment.
- Use outline treatment for secondary actions and avoid competing equally dominant buttons.
- Forms retain labels, validation, security behavior, readable states, visible focus, and mobile-safe control widths.

## Accessibility and motion

- Target practical WCAG 2.2 AA conformance: sufficient contrast, semantic structure, visible keyboard focus, meaningful links, responsive text, 44-pixel controls, and no keyboard traps.
- Preserve WordPress image alternative-text behavior and form label associations.
- Use short functional transitions only. Respect `prefers-reduced-motion` and do not introduce decorative animation systems.

## Medical trust boundary

- Do not rewrite medical claims or credentials as part of presentation work.
- Keep the public medical disclaimer visible and preserve Booking, Content Operations, SEO, and routing semantics.
- Production remains out of scope until separately authorized.

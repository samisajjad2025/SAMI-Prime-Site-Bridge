# SAMI Prime Funnel V3 — approval-gated conversion preview

## Baseline
- Production inspection 27 Nov 2026: Membership Funnel 1.4.6.1 and Landing CTA Repair 0.1.3 active.
- GitHub main base b207a325c9987d3cc882645d8404563444ceab1f contains existing repair, not verified full production funnel HTML.
- This separate plugin preserves all existing DOM sections, pricing, photograph, branding, links, original language selector, consent and checkout tracking. It adds a localized learning roadmap before the existing offers section, and navigational buttons to the original Starter/Combo cards.
- Exact previous 18 module titles and previous V3 ZIP were not recovered; the 6+6+6 curriculum here is a proposed draft, not a verified existing syllabus. Prices remain in original offer cards. No changes to payment provider or tracking.

## Preview only
Zip the spr-funnel-v3-conversion-preview directory, install/activate on staging or after a backup of the site. While logged in as an administrator open:
https://samiprimeresearch.in/stock-market-research-education/?spr_v3_preview=1
A public visitor must not receive this section. Verify page caching and iframe resizing before proceeding. Deactivation removes this add-on without replacing the original funnel. Do not publish to all visitors until offer scope, curriculum, translations, fee validity, KYC/terms and applicable advertising approvals are signed off.

## QA
JavaScript syntax parsed; mock-DOM tests cover five languages, 18 topics, one section, original offers preserved, Urdu RTL and two navigation destinations. No new checkout tracking calls. PHP lint, actual WordPress and real browser visual QA, iframe height, five-language native review, mobile 320/390/768 and desktop 1440, CTA destinations, consent/no consent, GA4/Meta deduplication and live Cosmofeed payment confirmation remain unverified. Do not label this production-ready.

## Paid-conversion measurement
Do not treat outbound click as payment. Track landing views, offer clicks, checkout intent, payment completed via verified Cosmofeed payment record and net paid subscriptions after refunds. Segment by plan/device/campaign/creative, preserve UTM parameters where provider supports them, and reconcile payment exports. No synthetic purchase events. The reported baseline is INR 7,027.35 spend and zero sales; validate dates and attribution separately. Avoid scaling ads before successful end-to-end verification.

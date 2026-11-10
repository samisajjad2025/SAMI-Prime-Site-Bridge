# Security and operational limitations

**Prototype; not independently audited. Test on staging.**

- Endpoints require WordPress authentication, a dedicated \`spsb_propose\` capability (or admin), and an explicit disabled-by-default enable switch.
- The remote role can propose and read only supported published-page metadata. It cannot publish pages, edit arbitrary post content, manage users, files, plugins, themes, server files or databases.
- The plugin accepts proposals for existing Heading/Text Editor Elementor widgets only, max 50 KB HTML; expected content SHA-256 must match the live widget.
- Proposed Text Editor HTML passes \`wp_kses_post()\` (which may remove unsupported HTML elements/attributes); headings use \`sanitize_text_field()\`.
- Only an authenticated WP administrator with an action-specific nonce can approve a proposal.
- The entire previous Elementor JSON for an affected page is stored privately in WP options before applying a change. Rollback is conditional on the page being unchanged since approval.
- A page snapshot **is not a complete website backup**. Full WordPress files and database must be backed up separately through Hostinger, with a verified restore method.
- Read/write access from ChatGPT requires a **separate connector**, a dedicated WordPress Application Password, least privileges and secure secret storage.
- Public GitHub releases may enable update checks after deliberate release tagging. The GitHub updater does not provide independent artifact signature verification; consider pinned dependencies, reviewed tags, branch protection and checksums before production use.
- Auto-updates on live WordPress must remain disabled until each release has passed staging tests.
- Financial and regulatory disclosures, SEBI numbers, grievance figures, subscription prices, and legal statements always require manual evidence verification and explicit admin approval.

Never use Hostinger's site-root Git deployment with this repository. It may overwrite WordPress.

# SAMI Prime Site Bridge — WordPress / Elementor (prototype)

Secure, human-approval-gated WordPress content change proposals for **SAMI PRIME RESEARCH** (Hostinger-hosted WordPress + Elementor).

> **Safety:** This repository is source code only. It does not deploy to, update, or connect ChatGPT to the live website. Staging, full site/database backups, installation, connector authentication, and explicit approval are all separate steps.

## Scope

- Authenticated read access to published page metadata and supported Elementor Heading/Text Editor widget previews and hashes.
- Authenticated submission of proposed text/HTML updates, not direct remote publishing.
- Final human review in **WordPress Admin → Tools → SAMI Site Bridge**; only approval applies the change.
- Original Elementor page JSON snapshot saved before approved change, conditional rollback, and audit events.
- No remote file writing, plugin/theme manipulation, server shell, SQL, user management, or unrestricted website control.
- Disabled by default; use a dedicated least-privileged WordPress account and revocable Application Password.

## Files

- `sami-prime-site-bridge/sami-prime-site-bridge.php`: plugin entry point.
- `sami-prime-site-bridge/readme.txt`: WordPress plugin readme.
- `docs/CONNECTOR.md`: authenticated REST usage.
- `docs/openapi.yaml`: proposed secure connector endpoints.
- `docs/SECURITY.md`: controls and limitations.
- `tests/widget-tests.php`: basic logic smoke tests (not a live WordPress integration test).
- `.github/workflows/release.yml`: build a release ZIP *only* when a version tag is pushed.

## Deployment rules

1. Review all source changes and run PHP/test checks.
2. Back up WordPress **files and database**; test a restore path.
3. Install and test on **staging** with the current WordPress and Elementor versions; verify HTML table styling and rollback.
4. Publish a reviewed GitHub release only after tests pass. The plugin can detect newer public GitHub releases; **do not enable auto-update on production before staging validation**.
5. Install the plugin ZIP through WordPress's plugin uploader — **never use Hostinger's site-root Git deployment** for this repository: it could overwrite WordPress files.
6. Connect a separate secure, approved API integration using a WordPress Application Password held in a secrets manager, never in code, chat, or GitHub.
7. Keep SEBI disclosure updates subject to manual verification and administrator approval.

**Status:** Prototype only. No live-site installation has been performed by this repository setup.

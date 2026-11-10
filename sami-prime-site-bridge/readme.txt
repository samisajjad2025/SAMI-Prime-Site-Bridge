=== SAMI Prime Site Bridge ===
Contributors: sami-prime-research
Tags: elementor, approval, content, site-management
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Authenticated, approval-gated Elementor Heading and Text Editor content proposals. Staging-first prototype.

== Description ==

Use a dedicated SAMI Site Bridge Agent WordPress user to inspect supported published-page widget metadata and submit content proposals. WordPress administrators approve changes manually in Tools > SAMI Site Bridge. No remote publish, arbitrary files, database, plugin or theme control.

The bridge starts DISABLED on activation. Staging and full site/database backups are essential before any production installation. This plugin is not a standalone ChatGPT connector.

== Installation ==

1. Create full WordPress files/database backup and a staging clone.
2. Upload ZIP via Plugins > Add New > Upload Plugin in STAGING.
3. Activate. Review Tools > SAMI Site Bridge; leave bridge disabled.
4. Create a least-privileged SAMI Site Bridge Agent account.
5. After separate secure connector setup, generate an Application Password and enable bridge on staging.
6. Test page discovery, proposal approval, Elementor rendering and rollback before any live deployment.

== Changelog ==

= 0.1.0 =
* Initial, disabled-by-default proposal workflow and optional GitHub release update checks.

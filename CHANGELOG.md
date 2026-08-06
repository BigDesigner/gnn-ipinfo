# Changelog

All notable changes to the **GNN IPinfo** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to Semantic Versioning.

## [0.3.0] - 2026-08-06
### Changed
- **Build:** Rebuilt the release workflow on the GNN standard: `v*` tag pushes now trigger a release automatically, and the version is resolved from the tag, then the manual override, then the `Version` header in `gnn-ipinfo.php`.
- **Build:** The plugin is now packaged from the repo root into a clean `dist/gnn-ipinfo/` folder and zipped as `gnn-ipinfo-v<version>.zip`.
### Added
- **Build:** Every workflow run now uploads the zip as a build artifact (90-day retention), so a package is downloadable even when no release is created.
- **Build:** New `create_github_release` input allows building the zip without publishing a release.

## [0.2.9] - 2026-08-02
### Changed
- **Admin:** Moved settings menu to top-level position `79.104` to align with the GNN Product Family menu position registry.

## [0.2.8] - 2026-05-07
### Added
- **Security:** Implemented comprehensive output escaping (`esc_html_e`, `esc_html__`) across the plugin for security compliance.
- **Security:** Added validation, unslashing, and sanitization for `$_SERVER['REMOTE_ADDR']`.
- **Standards:** Updated plugin headers with correct Author URI (GitHub) and License (GPLv2 or later).
### Removed
- **Cleanup:** Removed development debug logs (`error_log`) from production code.

## [0.2.7] - 2026-04-26
### Changed
- **Frontend:** Completely replaced the heavy frontend UI (cards, lists, buttons) with a lightweight, theme-agnostic `<pre><code>` block that prints raw JSON data ("anam babam usulü").
- **Performance:** Removed frontend loading of `style.css` and `copy-ip.js`, making the shortcode output virtually zero-impact on page speed.

## [0.2.6] - 2026-04-26
### Fixed
- **UI:** Pushed a hotfix to ensure the plain text action links layout is properly released and active for all users.

## [0.2.5] - 2026-04-26
### Changed
- **UI:** Reverted plugin action links to plain text to match the clean aesthetic of GNN Whois.
### Removed
- **UI:** Removed the redundant "Check for Updates Now" button from the Settings page since it's now accessible via the Plugins page action links.

## [0.2.3] - 2026-04-26
### Fixed
- **Security:** Conducted full security audit and implemented ABSPATH guard in all PHP files.
- **Security:** Enhanced data sanitization and output escaping throughout the plugin.

## [0.2.2] - 2026-04-26
### Fixed
- **UI:** Forced single column layout for IP data cards as requested.
- **UI:** Improved vertical alignment and spacing for a cleaner look.

## [0.2.1] - 2026-04-26
### Changed
- **UI:** Switched to a more minimalist and compact layout.
- **UI:** Reduced container padding and margins to match theme standards.
- **UI:** Increased horizontal width for better data presentation.
- **UI:** Scaled down IP text and list item heights for a cleaner aesthetic.

## [0.2.0] - 2026-04-26
### Added
- **Premium UI:** Advanced Glassmorphism design with universal Dark/Light theme compatibility.
- **Performance:** 1-hour transient caching for API requests.
- **Utility:** "Copy to Clipboard" button for the IP address.
- **Admin:** "GNN System Info" card with dynamic version display and secure manual update check.
- **Security:** Nonce verification and enhanced data sanitization.
- **Debug:** New "Debug Mode" for administrators to view raw API data.

## [0.1.0] - 2026-04-26
### Added
- Initial project structure for GNN IPinfo plugin.
- Established Project Memory Bank (Snapshot, Standards, Guardrails).
- Implemented core IPinfo.io API integration.
- Added `[gnn_ipinfo]` shortcode to display visitor IP data.
- Created Settings page for API Token management.
- Added Turkish (tr_TR) localization support.
- Implemented basic CSS for frontend and backend displays.

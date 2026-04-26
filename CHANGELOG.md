# Changelog

All notable changes to the **GNN IPinfo** plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to Semantic Versioning.

## [0.2.5] - 2026-04-26
### Added
- **UI:** Refactored plugin action links on the plugins page to include Dashicons (Settings, Update, Donate) and improved flexbox styling for a premium aesthetic.
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

# Project Snapshot: GNN IPinfo

## System Status
- **Plugin Version:** 0.1.1
- **Current Branch:** main
- **Core Principles:** 
    - Zero 3rd-party Dependency (PHP side)
    - Native WordPress Settings API focus
    - IPinfo.io API Integration
    - Performance via Transients (Pending implementation/optimization)

## Active Features
- **Admin Settings Page:** API Token configuration.
- **Shortcode `[gnn_ipinfo]`:** Displays visitor IP information.
- **Localization:** Turkish (tr_TR) support.
- **Admin Integration:** Quick links (Settings, Donate) on the Plugins page.
- **Security:** Basic sanitization and escaping implemented.

## Module Map
| File | Responsibility |
|------|---------------|
| `gnn-ipinfo.php` | Main plugin file, hooks, settings registration, shortcode logic. |
| `style.css` | Frontend and Backend styling for IP information display. |
| `languages/` | Translation files (.po, .mo). |
| `memory-bank/` | Project documentation and state management. |

## Development Status
- **Phase:** Active Development / Refinement.
- **Upcoming Goals:**
    - Implement Transient caching for API responses.
    - Improve error handling for API failures.
    - Enhance UI/UX of the IP information display (GNN Premium aesthetics).
    - Add "Copy IP" functionality.
    - Implement a "Debug Mode" toggle in settings.

## Environment
- **Platform:** WordPress
- **PHP Version:** >= 7.4
- **API:** IPinfo.io (Token required)

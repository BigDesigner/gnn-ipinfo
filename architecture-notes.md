# Architecture Notes: GNN IPinfo

## System Design
GNN IPinfo is a modular, lightweight WordPress plugin designed to retrieve and display visitor IP information. It follows a functional programming approach, utilizing native WordPress APIs to ensure compatibility and performance.

## Data Flow
1. **Initiation:** A user visits a page containing the `[gnn_ipinfo]` shortcode.
2. **Shortcode Execution:** The `gnn_ipinfo_shortcode()` function is triggered.
3. **Data Retrieval:**
    - Checks for a valid API Token in the database.
    - Checks the **Transients API** for cached data for the current visitor's IP.
    - If no cache exists, it performs a remote request to `ipinfo.io` via `wp_remote_get()`.
4. **Data Processing:**
    - Validates and sanitizes the JSON response.
    - Caches the result using transients (1-hour expiration).
5. **Output Rendering:** Assembles the HTML container and outputs the visitor information.

## Key Components
- **Settings API:** Handles the registration, sanitization, and storage of the IPinfo API token in the `wp_options` table.
- **Shortcode API:** Provides a simple `[gnn_ipinfo]` tag for users to place anywhere in their content.
- **Transients API:** Reduces external API dependency by caching results locally, improving page load times for returning visitors and reducing API credit usage.
- **Admin UI:** Implements a clean, native settings page under "Settings > GNN IPinfo" with custom "GNN Premium" aesthetics.
- **Localization:** Full support for internationalization (i18n) via `.po`/`.mo` files.

## External Services Integration
1. **IPinfo.io:** Primary data provider for IP geolocation, ASN, and organizational data. Requires an API token for authenticated requests.

## Core Principles
1. **Zero 3rd-party Dependency (PHP):** The plugin must not rely on external PHP libraries. Use native WordPress functions for HTTP requests, caching, and sanitization.
2. **Native UX:** Settings and UI elements should feel like a part of WordPress but with a modern "GNN" touch.
3. **Security First:** Strict escaping on output and sanitization on input. Nonce verification for all settings changes.
4. **Performance Focused:** Minimize API calls through aggressive (but safe) caching.

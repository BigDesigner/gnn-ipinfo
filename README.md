# GNN IPinfo

A lightweight, bulletproof WordPress plugin that retrieves and displays visitor IP information using the [IPinfo.io](https://ipinfo.io) API. Part of the GNN Product Family.

---

## 🚀 Features

- **Lightweight & Theme-Agnostic:** Outputs clean, formatted JSON within a universal code block that integrates seamlessly into any WordPress theme without heavy CSS or JS overhead.
- **Robust IP Detection:** Automatically resolves visitor IP addresses, with native support for Cloudflare (`HTTP_CF_CONNECTING_IP`) and standard reverse proxies, with strict format validation.
- **Aggressive Caching & Failure Backoff:** Caches API responses using WordPress Transients for 1 hour to prevent unnecessary external requests. In case of API failure or rate limiting, automatically engages a 3-minute backoff transient to prevent site slowdowns.
- **Admin Diagnostics (Debug Mode):** When enabled in settings, logged-in administrators can view diagnostic metadata (cache HIT/MISS, resolved client IP, and header source) directly under the shortcode.
- **Seamless GitHub Updates:** Integrated automatic updater that checks GitHub Releases for new versions and updates directly via the WordPress dashboard.
- **Standardized Menu Position:** Sits at position `79.104` under the GNN Product Family standard.
- **Clean Database Uninstaller:** Includes `uninstall.php` to completely clean up options and cached transients upon deletion.
- **Zero Third-Party PHP Dependencies:** Built exclusively on native WordPress APIs (`wp_remote_get`, Settings API, Transients API).

---

## 📦 Installation

1. Download the latest `gnn-ipinfo-v*.zip` from the [Releases](https://github.com/BigDesigner/gnn-ipinfo/releases) page.
2. In your WordPress admin, go to **Plugins > Add New Plugin > Upload Plugin**, select the `.zip` file, and click **Install Now**.
3. Activate the plugin through the **Plugins** menu.
4. Navigate to **GNN IPinfo** in the WordPress sidebar (near Settings, position 79.104).
5. Enter your [IPinfo.io](https://ipinfo.io/account/token) API token and save.

---

## 💻 Usage

Place the shortcode anywhere in your posts, pages, or widget blocks:

```text
[gnn_ipinfo]
```

---

## 🛠️ Developer Hooks & Extensibility

### Customize Client IP Detection
Override or sanitize the client IP address (useful for custom load balancers or proxy clusters):

```php
add_filter('gnn_ipinfo_client_ip', function ($ip) {
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP']));
    }
    return $ip;
});
```

### Customize Cache Duration
Adjust the default transient caching duration (default: 1 hour / 3600 seconds):

```php
add_filter('gnn_ipinfo_cache_duration', function ($seconds) {
    return 2 * HOUR_IN_SECONDS; // Cache for 2 hours
});
```

---

## 🔒 Security

- Strict `ABSPATH` guards on all PHP entrypoints.
- Full output escaping with `esc_html()`, `esc_attr()`, and `esc_url()`.
- Nonce protection and `manage_options` / `update_plugins` capability checks.
- Password-masked token input field in the admin dashboard.

---

## 📄 License

GPLv2 or later. Built with ❤️ by [BigDesigner](https://github.com/BigDesigner).

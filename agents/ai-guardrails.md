# AI Agent Zero-Tolerance Coding Standard (Plugin Edition)

> **MANDATORY READING.** Every AI agent working on GNN plugins MUST read and internalize
> this document BEFORE writing code. Violations are UNACCEPTABLE.
> The human owner should NEVER have to catch security leaks or prefixing errors.

---

## ABSOLUTE RULES — NO EXCEPTIONS

### 1. Global Prefixing — ALWAYS
Every function, global variable, and CSS class MUST be prefixed with `gnn_ipinfo_` (or the project-specific prefix).

```php
/* ❌ FORBIDDEN — might collide with other plugins */
function get_ip_data() { ... }

/* ✅ REQUIRED */
function gnn_ipinfo_get_ip_data() { ... }
```

### 2. Sanitization and Escaping — DEFENSE IN DEPTH
Never trust user input or API responses.
- **Input:** Use `sanitize_text_field()`, `absint()`, `sanitize_email()`, etc.
- **Output:** Use `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.

```php
/* ❌ DANGEROUS — raw output */
echo $data['ip'];

/* ✅ SECURE */
echo esc_html($data['ip']);
```

### 3. Nonce Verification — MANDATORY
Every form submission, AJAX request, or admin action MUST verify a nonce.

```php
/* ✅ REQUIRED in processing logic */
if ( ! isset( $_POST['gnn_ipinfo_nonce'] ) || ! wp_verify_nonce( $_POST['gnn_ipinfo_nonce'], 'gnn_ipinfo_save_settings' ) ) {
    wp_die( 'Security check failed' );
}
```

### 4. Permission Checks — MANDATORY
Every admin-facing function MUST check if the current user has permission.

```php
/* ✅ REQUIRED before any admin action */
if ( ! current_user_can( 'manage_options' ) ) {
    return;
}
```

### 5. Transient Caching — API BEST PRACTICE
External API calls (like IPinfo) MUST be cached using Transients to prevent rate-limiting and slow page loads.

```php
/* ✅ REQUIRED for external requests */
$cache_key = 'gnn_ipinfo_' . md5($ip);
$data = get_transient($cache_key);

if ( false === $data ) {
    // Perform API call
    set_transient($cache_key, $data, HOUR_IN_SECONDS);
}
```

### 6. Theme Compatibility — ZERO CONFLICT CSS
The plugin MUST work on any theme (Black, White, Colorful).
- **Colors:** Never hardcode text colors as `#000` or `#fff`. Use `color: inherit;` or CSS variables with very safe fallbacks.
- **Backgrounds:** Use semi-transparent backgrounds (rgba) or glassmorphism (`backdrop-filter`) to ensure the container looks premium on any theme background.
- **Borders:** Use `currentColor` or very low-opacity borders.

```css
/* ✅ REQUIRED — works on both dark and light themes */
.gnn-ipinfo-container {
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(10px);
    color: inherit;
    border: 1px solid rgba(128, 128, 128, 0.2);
}
```

### 7. ABSPATH Guard — FILE SECURITY
Every PHP file MUST start with the ABSPATH check to prevent direct access.

```php
<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
```

---

## MANDATORY PRE-COMMIT VERIFICATION

Before ANY commit, the AI agent MUST verify:

1. **Prefix Audit:** Search for functions/classes and ensure they all have the `gnn_ipinfo_` prefix.
2. **Security Audit:** Verify `esc_html` is used on ALL outputs and `sanitize_` on ALL inputs.
3. **Nonce Audit:** Ensure all `POST` or `GET` actions are protected by nonces.
4. **Capability Audit:** Verify `current_user_can()` is used in admin functions.
5. **Caching Audit:** Verify external API results are stored in transients.
6. **Localization Audit:** Ensure ALL strings are wrapped in `__()` or `_e()`.

---

## THE STANDARD

> **If a human has to find a security flaw or a naming collision, you have failed.**
> Write code that is isolated, secure, and performs optimally.
> Zero tolerance. Zero excuses.

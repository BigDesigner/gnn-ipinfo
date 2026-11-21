# ADR 0001: Unified GNN GitHub Updater Standard

- **Status**: Accepted
- **Date**: 2026-11-21
- **Supersedes**: None
- **Superseded By**: None

## Context
`gnn-ipinfo/inc/updater.php` (`GNN_IPinfo_Updater`) lacked several reliability and lifecycle guarantees required by the Unified GNN GitHub Updater Standard:
- No fallback when `api.github.com` is rate-limited or returns non-200 responses.
- No semver validation on `tag_name` and used `strpos($name, '.zip')` instead of suffix matching.
- `check_for_update` did not unset `$transient->response` when local version was up to date, and omitted `requires`, `tested`, `requires_php` on update objects.
- `after_install` did not capture/restore active plugin state via `upgrader_pre_install`, did not normalize paths with `wp_normalize_path`, did not initialize `$wp_filesystem` if missing, and did not call `wp_clean_plugins_cache(true)`.

## Decision
Standardized `GNN_IPinfo_Updater` in `gnn-ipinfo/inc/updater.php` to the Unified GNN GitHub Updater Standard:
1. **Hooks**: Registered `pre_set_site_transient_update_plugins`, `plugins_api` (20, 3), `upgrader_pre_install` (`capture_active_state`), `upgrader_post_install` (`after_install`, 10, 3), `admin_init` (`handle_manual_check`), and `load-update-core.php` (`clear_cache`).
2. **Release Fetching & Fallback**: Added `wp_remote_head(..., ['redirection' => 0])` + `/releases/expanded_assets/{tag}` fallback, semver validation (`^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?$`), `.zip` suffix matching, SSRF allowlist (`sanitize_github_url`), 12-hour positive cache, and 300-second `(object)['failed' => true]` negative cache.
3. **Transient & Upgrade Lifecycle**: Ensured `response` and `no_update` arrays are mutually exclusive per plugin key, added `wp_normalize_path` + `$wp_filesystem` initialization + `wp_clean_plugins_cache(true)` + `$this->was_active` reactivation in `after_install`, and kept `clear_cache()` scoped to `$this->transient_key`.

## Consequences
- Resilient update discovery even under GitHub API rate limits.
- Reliable directory normalization and plugin reactivation across upgrades.
- Consistent updater contract across the GNN plugin ecosystem.

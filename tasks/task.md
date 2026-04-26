# Task List

## Completed
- [x] MB-001: Create Memory Bank structure.
- [x] MB-002: Initialize boilerplate documentation.
- [x] MB-003: Create .gitignore file.
- [x] OPS-001: Create GitHub Actions Release workflow.
- [x] ELM-001: Implement Elementor Compatibility Layer.
- [x] ELM-002: Create Elementor-specific "Page Builder" templates (Full Width + Canvas).
- [x] ELM-003: Optimize theme CSS for Elementor reset compatibility.
- [x] DEV-001: Analyze existing theme logic for modularity.
- [x] SEO-001: Implement meta tag management (OG, Twitter, Schema, per-post SEO metabox).
- [x] CUST-001: Implement Native Customizer API (Header, Footer, Typography).
- [x] CUST-003: Integrate Selective Refresh for real-time Customizer updates.
- [x] CUST-004: Implement `theme.json` Design System (Gutenberg & Site Editor sync).
- [x] UI-001: Design responsive navigation.
- [x] CUST-002: Add Native Slider/Carousel controls via Customizer.
- [x] SEC-001: Implement Cloudflare Turnstile integration for login/forms.
- [x] SEC-002: Implement Google reCAPTCHA v3 fallback integration.
- [x] ANA-001: Implement Google Analytics (GA4) integration via Customizer.
- [x] PERF-001: Implement asset optimization (minification, critical CSS).
- [x] UI-002: Implement Custom Magnetic Cursor via GSAP.
- [x] A11Y-001: Accessibility audit and improvements.
- [x] I18N-001: Translation-readiness audit and .pot file generation.
- [x] TMPL-001: Complete WordPress template hierarchy (single, archive, search, 404, comments).

## Sprint v1.7.1 — Static Hero Image ✅ PASS
- [x] HERO-001: Add Static Hero Image support to Customizer. → **PASS**
- [x] HERO-002: Implement static hero image rendering in `header.php`. → **PASS**
- [x] HERO-003: Add `.gnn-hero-static-wrapper` CSS. → **PASS**
- [x] HERO-004: Verify sanitization, escaping, i18n compliance. → **PASS**
- [x] DOC-001: Update CHANGELOG.md, style.css → 1.7.1, snapshot.md. → **PASS**

## Sprint v1.8.0 — GitHub Auto-Updater ✅ PASS
- [x] UPD-001: Create `inc/updater.php` — GNN_GitHub_Updater class. → **PASS**
- [x] UPD-002: Register module in `functions.php`. → **PASS**
- [x] UPD-003: Update `release.yml` rsync excludes. → **PASS**
- [x] UPD-004: Verify sanitization, escaping, error handling. → **PASS**
- [x] DOC-001: Bump version → 1.8.0, CHANGELOG, snapshot. → **PASS**

## Sprint v1.8.1 — Update Control Panel ✅ PASS
- [x] UPD-005: Add Customizer toggle (`enable_github_updates`) to enable/disable auto-updates. → **PASS**
- [x] UPD-006: Create admin page (Appearance > Theme Updates) with status dashboard. → **PASS**
- [x] UPD-007: Implement "Check for Updates Now" button with nonce protection. → **PASS**
- [x] UPD-008: Verify all new admin output is escaped and nonce-protected. → **PASS**
- [x] DOC-002: Bump version → 1.8.1, CHANGELOG, snapshot. → **PASS**

## Backlog
- (empty)

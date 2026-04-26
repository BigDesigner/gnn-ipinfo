# Architecture Decision Records (ADR)

## [ADR-001] Memory Bank System
- **Status:** Accepted
- **Context:** Need for persistent context management across AI sessions.
- **Decision:** Implement a structured `/memory-bank/` and support directories.
- **Consequences:** Higher overhead for session starts, but zero context loss.

## [ADR-002] Functional vs Object-Oriented Approach
- **Status:** Accepted
- **Context:** The plugin is currently small and uses functional hooks.
- **Decision:** Stick to a functional approach with strict `gnn_ipinfo_` prefixing for now.
- **Rationale:** Minimizes complexity and overhead for a simple IP retrieval utility.
- **Consequences:** Easy to read and debug for WordPress developers. May need refactoring to OOP if the plugin grows significantly.

## [ADR-003] Transient Caching for API Data
- **Status:** Accepted
- **Context:** IPinfo.io has rate limits and external requests slow down page loads.
- **Decision:** Use the WordPress Transients API to cache IP data for 1 hour.
- **Rationale:** Improves performance and prevents unnecessary API credit consumption.
- **Consequences:** Data may be slightly outdated (e.g., if a user changes locations quickly), but 1 hour is a safe balance.

## [ADR-004] Settings API for Configuration
- **Status:** Accepted
- **Context:** Need a way to store the API Token and other settings.
- **Decision:** Use the native WordPress Settings API and `register_setting()`.
- **Rationale:** Best practice for WordPress plugins, handles sanitization and security out of the box.
- **Consequences:** Clean integration with the WordPress admin interface.

# Changelog

## [0.9.0] - 2026-06-26

### Added

- Session-based authentication.
- Multiple login fields.
- Manual login support.
- Login with external providers.
- Remember Me using selector/token strategy.
- Login rate limiting.
- Role-based permissions.
- Wildcard permissions.
- User profiles.
- AuthUser and Profile data objects with ArrayAccess.
- Authentication diagnostics.
- Configuration publisher.
- CLI commands.
- Dedicated exceptions.

### Changed

- login_field now accepts string or array.
- remember_lifetime replaces remember_days.

### Fixed

- ...
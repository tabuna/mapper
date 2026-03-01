# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- `with(callable|string)` custom mapper API.
- `mapUsingContainer(...)` for explicit container resolution.
- `into(...)` and `intoMany(...)` one-shot mapping API.
- `useContainer(...)` for one-time global container bootstrap.
- `usePsrContainer(...)` for one-time PSR-11 container bootstrap (Symfony-ready).
- `mapUsingPsrContainer(...)` for explicit one-shot PSR-11 mapping.
- `resetContainer()` to clear global container configuration.
- `toMany(...)` explicit collection mapping API.
- Runtime auto-detection for containers from `$GLOBALS['kernel']` and `$GLOBALS['container']`.
- Laravel auto-wiring through package service provider.
- Symfony integration bundle (`TabunaMapBundle`).
- Integration docs for Symfony and WordPress.
- Contribution guide, roadmap, and comparison notes.
- CI coverage workflow with 100% line-coverage gate.

### Changed

- Improved mapper safety for non-public and readonly properties.
- Improved source normalization behavior and API documentation.
- Removed `map_into(...)` and `map_into_many(...)` to keep API surface minimal.

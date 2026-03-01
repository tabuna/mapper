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
- `rename([...])` for source-to-target key aliases.
- `snakeToCamelKeys()` for snake_case/kebab-case payloads.
- `strict()` mode to fail on unknown attributes.
- `only([...])` and `except([...])` for payload key filtering.
- `path('dot.notation')` to map nested payload envelopes.
- Runtime auto-detection for containers from `$GLOBALS['kernel']` and `$GLOBALS['container']`.
- Laravel auto-wiring through package service provider.
- Symfony integration bundle (`TabunaMapBundle`).
- Integration docs for Symfony and WordPress.
- Contribution guide, roadmap, and comparison notes.
- Security policy and code of conduct.
- CI coverage workflow with 100% line-coverage gate.
- Reproducible benchmark script (`composer bench`).
- Automated GitHub release workflow for version tags.

### Changed

- Improved mapper safety for non-public and readonly properties.
- Improved source normalization: request-like objects are mapped directly via `all()`, `get_params()`, or `getParsedBody()`.
- Added Laravel FormRequest-aware source normalization: `validated()` / `safe()->all()` now has priority over raw `all()`.
- Restricted validated payload extraction to explicit supported sources (`Illuminate\Foundation\Http\FormRequest`) instead of generic method probing.
- Added Symfony request bag normalization (`$request->request->all()` / query bag) for zero-config mapping.
- Added HTTP client response normalization for Laravel HTTP (`json()` / `body()`) and Guzzle/PSR-7 (`getBody()`).
- Restricted HTTP response extraction to explicit supported classes/contracts (`Illuminate\Http\Client\Response`, `Psr\Http\Message\ResponseInterface`).
- Reduced hard framework coupling: `illuminate/http` and `illuminate/database` are now optional dependencies.
- Refactored internals: `Mapper` now delegates to dedicated support components (container, normalization, rules, factory, hydrator).
- Moved framework runtime probing into dedicated `FrameworkContainerDetector`.
- Moved Eloquent-specific behavior into dedicated `EloquentModelSupport`.
- Removed `map_into(...)` and `map_into_many(...)` to keep API surface minimal.
- Added constructor-argument mapping for immutable/readonly DTOs.

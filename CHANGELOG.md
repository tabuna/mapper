# Changelog

All notable changes to this project will be documented in this file.

## Unreleased

### Added

- `with(callable|string)` custom mapper API.
- `withSourceExtractor(...)` for custom object source payload extraction.
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
- Improved source normalization: framework payload extraction now relies on explicit supported classes/contracts instead of generic method probing.
- Added Laravel FormRequest-aware source normalization: `validated()` / `safe()->all()` now has priority over raw `all()`.
- Added Laravel Validator contract support: `Illuminate\Contracts\Validation\Validator` now maps via `validated()`.
- Added Eloquent source normalization via `attributesToArray()` to avoid mapping relation tree by default.
- Restricted validated payload extraction to explicit supported sources (`Illuminate\Foundation\Http\FormRequest`, `Illuminate\Contracts\Validation\Validator`) instead of generic method probing.
- Added ObjectMapper-style attribute API: `#[Tabuna\Map\Attribute\Map]` for class/property mapping metadata.
- Added mapping to existing object instances via `map($source)->to($existingObject)`.
- Added class-level target inference via `map($source)->to()` when source has `#[Map(target: ...)]`.
- Added `if` and `transform` mapping callables with service-class resolution support.
- Added class-level transform support before hydration.
- Added source-vs-target metadata precedence: source property mapping rules override target-side source mapping rules.
- Added Symfony request bag normalization (`$request->request->all()` / query bag) for zero-config mapping.
- Added HTTP client response normalization for Laravel HTTP (`json()` / `body()`) and Guzzle/PSR-7 (`getBody()`).
- Restricted HTTP response extraction to explicit supported classes/contracts (`Illuminate\Http\Client\Response`, `Psr\Http\Message\ResponseInterface`).
- Added explicit runtime container contracts (`KernelContainerProvider`, `SymfonyContainerLike`) and removed container `method_exists` probing.
- Refactored internal source/target/container/rules components into dedicated top-level directories (`src/Container`, `src/Source`, `src/Target`, `src/Transform`).
- Split monolithic `MapperTest` into focused test suites by behavior domain for maintainability.
- Reduced hard framework coupling: `illuminate/http` and `illuminate/database` are now optional dependencies.
- Refactored internals: `Mapper` now delegates to dedicated support components (container, normalization, rules, factory, hydrator).
- Moved framework runtime probing into dedicated `FrameworkContainerDetector`.
- Moved Eloquent-specific behavior into dedicated `EloquentModelSupport`.
- Removed `map_into(...)` and `map_into_many(...)` to keep API surface minimal.
- Added constructor-argument mapping for immutable/readonly DTOs.

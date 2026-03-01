# Why tabuna/map

## Main Advantages

- Small API surface (`map`, `to`, `toMany`, `with`) with predictable behavior.
- Works with plain PHP objects and Eloquent models.
- Explicit extension point for custom mapping logic.
- Strong quality gates in CI (tests, style, static analysis, coverage target).

## Compared to Manual Mapping

- Less repetitive boilerplate.
- Centralized mapping behavior.
- Easier consistency across controllers, jobs, and services.

## Compared to Heavy DTO Frameworks

- Lower setup cost.
- Faster first result for common request/array-to-object mapping.
- Simpler migration path: start with default mapping, then add custom mappers only where needed.

# Contributing

Thanks for improving `tabuna/map`.

## Development Setup

```bash
composer install
```

## Architecture Map

- `src/Mapper.php`: thin public API facade.
- `src/Support/ContainerResolver.php`: runtime container strategy.
- `src/Support/FrameworkContainerDetector.php`: framework runtime probes (Laravel/Symfony/global).
- `src/Support/SourceNormalizer.php`: input normalization from arrays/objects/requests/JSON.
- `src/Support/Source/Extractors/*`: framework/client-specific payload extractors (Laravel request, HTTP clients, Symfony bags).
- `src/Support/AttributeRules.php`: payload shaping and key transforms.
- `src/Support/TargetFactory.php`: constructor-aware target creation.
- `src/Support/TargetHydrator.php`: property fill + strict validation.
- `src/Support/EloquentModelSupport.php`: isolated Eloquent-specific hydration/strict rules.
- `src/Support/helpers.php`: only global `map()` helper, no framework-specific behavior.

## Quality Checklist

Run all checks before opening a PR:

```bash
vendor/bin/pint --test
vendor/bin/phpunit
composer psalm
```

## Pull Request Rules

- Keep PRs focused on one improvement.
- Add or update tests for behavior changes.
- Update docs when public API changes.
- Preserve backward compatibility unless change is clearly documented.

## Commit Style

Use short imperative commit messages, for example:

- `Add toMany API for explicit collection mapping`
- `Document Symfony integration example`

# Contributing

Thanks for improving `tabuna/map`.

## Development Setup

```bash
composer install
```

## Architecture Map

- `src/Mapper.php`: thin public API facade.
- `src/Container/ContainerResolver.php`: runtime container strategy.
- `src/Container/FrameworkContainerDetector.php`: framework runtime probes (Laravel/Symfony/global).
- `src/Container/Contracts/*`: explicit container-related contracts for supported runtime objects.
- `src/Source/SourceNormalizer.php`: input normalization from arrays/objects/requests/JSON.
- `src/Source/Extractors/*`: framework/client-specific payload extractors (Laravel request, HTTP clients, Symfony bags).
  Keep framework extractors explicit (contracts/classes), avoid broad method probing for framework-only behavior.
- `src/Transform/AttributeRules.php`: payload shaping and key transforms.
- `src/Target/TargetFactory.php`: constructor-aware target creation.
- `src/Target/TargetHydrator.php`: property fill + strict validation.
- `src/Target/EloquentModelSupport.php`: isolated Eloquent-specific hydration/strict rules.
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

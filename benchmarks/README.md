# Benchmarks

Run:

```bash
composer bench
```

What it measures:

- Manual object mapping baseline
- `Mapper::into(...)` single-item mapping
- Manual collection mapping baseline
- `Mapper::intoMany(...)` collection mapping

The script prints elapsed time and overhead relative to manual mapping.

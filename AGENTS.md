# Current

PHP monorepo of Current's `current/*` Laravel packages. Contributor workflow is in
[CONTRIBUTING.md](CONTRIBUTING.md); layout and releases are in [docs/monorepo.md](docs/monorepo.md).

## Checks

Run `composer check` before handing off. Limit it with package names, or run one
step with `composer format:check`, `composer analyse`, or `composer test`.
`composer audit` is separate and needs network access. The shared standard is the
workspace toolchain standard.

## Rules

- `composer.lock` stays ignored; the packages are libraries.
- Keep `mise.toml`, the CI matrix, and the packages' minimum PHP aligned.
- Do not weaken PHPStan, Pint, or test settings to make a check pass.

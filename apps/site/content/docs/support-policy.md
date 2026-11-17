---
title: Support policy
label: Support policy
description: Which Laravel and PHP versions Current supports, which releases get fixes, and how to report a security issue.
order: 6
---

## Supported versions

| Package | Laravel | PHP |
| --- | --- | --- |
| `current/current` 0.3 | 13 | 8.3 or later |
| `current/inertia` 0.2 | 13, with `inertiajs/inertia-laravel` 3 | 8.3 or later |

Current is tested on PHP 8.3, 8.4, and 8.5, with both the lowest and the latest allowed dependencies. Support for earlier Laravel versions isn't planned; it will be considered if adopters ask for it.

## While Current is on 0.x

- Each package is versioned on its own.
- Only the latest minor version of each package receives fixes.
- A minor release, such as 0.3 to 0.4, may change the public API. Every breaking change is listed under **Breaking** in the package's changelog.
- A patch release, such as 0.3.0 to 0.3.1, contains only compatible fixes.
- On 0.x, a caret constraint such as `^0.3` stays within one minor version, so Composer never moves you to a release that may break your code.

The public API is everything not marked `@internal`. The [contributing guide](https://github.com/currentphp/monorepo/blob/main/CONTRIBUTING.md#public-api) defines it in full.

## After 1.0

Breaking changes will need a major release, and a support window for previous major versions will be defined then.

## Reporting a security issue

Please don't open a public issue. Report it privately through [GitHub's private vulnerability reporting](https://github.com/currentphp/monorepo/security/advisories/new) on the monorepo.

## Getting help

Questions, bug reports, and feedback are welcome in [GitHub issues](https://github.com/currentphp/monorepo/issues). Pull requests are limited to maintainers until the API settles.

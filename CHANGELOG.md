# Changelog

All notable changes to this project will be documented in this file.

## v2.0.0 (2026-02-05)

- Statamic 6 support (Vue 3)
- Fix reactive sibling field value tracking — use publish container values directly instead of fragile DOM/component lookup
- Allow "Save as: Index" for simple string arrays
- Add Pest test suite for `{{ fir }}` tag

## v1.4.0 (2025-11-04)

- Fix globals on multisites — source global data retrieval now uses URL query parameter for more reliable site detection ([#1](https://github.com/eminos/statamic-field-item-relationship/pull/1), [#2](https://github.com/eminos/statamic-field-item-relationship/pull/2)) — thanks [@salamanderbe](https://github.com/salamanderbe)

## v1.3.0 (2024-05-09)

- Add Statamic 5 support

## v1.2.1 (2023-10-18)

- Fix build/minification issue

## v1.2.0 (2023-10-18)

- Add string literal option for advanced option label formatting
- Add `field` and `path` parameter options to the `{{ fir }}` tag
- Bug fixes

## v1.1.0 (2023-10-10)

- Improve source field search method
- Improve option label handling

## v1.0.0 (2023-09-29)

- Initial release

# Changelog

## [2.0.0](https://github.com/flarum/flarum/compare/v2.0.0-rc.8...v2.0.0)

### Added
- Deck extension

### Changed
- Updated constraints of core for v2.0.0 stable release
- `minimum-stability` is now `stable`, so `composer create-project flarum/flarum` no longer needs `--stability`

## [2.0.0-rc.8](https://github.com/flarum/flarum/compare/v2.0.0-rc.7...v2.0.0-rc.8)

No changes.

## [2.0.0-rc.7](https://github.com/flarum/flarum/compare/v2.0.0-rc.6...v2.0.0-rc.7)

No changes.

## [2.0.0-rc.6](https://github.com/flarum/flarum/compare/v2.0.0-rc.5...v2.0.0-rc.6)

No changes.

## [2.0.0-rc.5](https://github.com/flarum/flarum/compare/v2.0.0-rc.4...v2.0.0-rc.5)

No changes.

## [2.0.0-rc.4](https://github.com/flarum/flarum/compare/v2.0.0-rc.3...v2.0.0-rc.4)

### Added
- Audit extension

## [2.0.0-rc.3](https://github.com/flarum/flarum/compare/v2.0.0-rc.2...v2.0.0-rc.3)

No changes.

## [2.0.0-rc.2](https://github.com/flarum/flarum/compare/v2.0.0-rc.1...v2.0.0-rc.2)

No changes.

## [2.0.0-rc.1](https://github.com/flarum/flarum/compare/v2.0.0-beta.8...v2.0.0-rc.1)

### Changed
- Nginx config caches `.webp` images alongside other images [#97]

## [2.0.0-beta.8](https://github.com/flarum/flarum/compare/v2.0.0-beta.7...v2.0.0-beta.8)

### Added
- Realtime extension

### Removed
- Pusher extension, replaced by Realtime

## [2.0.0-beta.7](https://github.com/flarum/flarum/compare/v2.0.0-beta.6...v2.0.0-beta.7)

No changes.

## [2.0.0-beta.6](https://github.com/flarum/flarum/compare/v2.0.0-beta.5...v2.0.0-beta.6)

No changes.

## [2.0.0-beta.5](https://github.com/flarum/flarum/compare/v2.0.0-beta.3...v2.0.0-beta.5)

No changes.

## [2.0.0-beta.3](https://github.com/flarum/flarum/compare/v2.0.0-beta.2...v2.0.0-beta.3)

No changes.

## [2.0.0-beta.2](https://github.com/flarum/flarum/compare/v2.0.0-beta.1...v2.0.0-beta.2)

No changes.

## [2.0.0-beta.1](https://github.com/flarum/flarum/compare/v1.8.0...v2.0.0-beta.1)

### Added
* Messages extension
* GDPR extension

## [1.8.0](https://github.com/flarum/flarum/compare/v1.7.0...v1.8.0)

No changes.

## [1.7.0](https://github.com/flarum/flarum/compare/v1.6.0...v1.7.0)

No changes.

## [1.6.0](https://github.com/flarum/flarum/compare/v1.5.0...v1.6.0)

No changes.

## [1.5.0](https://github.com/flarum/flarum/compare/v1.4.0...v1.5.0)

### Changed

- Update copyright [#85]
- Link logo to official website [#84]

## [1.4.0](https://github.com/flarum/flarum/compare/v1.3.0...v1.4.0)

No changes.

## [1.3.0](https://github.com/flarum/flarum/compare/v1.2.0...v1.3.0)

No changes.

## [1.2.0](https://github.com/flarum/flarum/compare/v1.1.0...v1.2.0)

No changes.

## [1.1.0](https://github.com/flarum/flarum/compare/v1.0.0...v1.1.0)

No changes.

## [1.0.0](https://github.com/flarum/flarum/compare/v0.1.0-beta.16...v1.0.0)

### Changed
- Updated constraints of core and bundled extensions for v1.0.0 stable release (https://github.com/flarum/flarum/pull/74)

## [0.1.0-beta.16](https://github.com/flarum/flarum/compare/v0.1.0-beta.15...v0.1.0-beta.16)

### Changed
- Remove list of developers and refer to https://flarum.org/team (https://github.com/flarum/flarum/pull/71)

### Fixed
- Missing image on README.md (https://github.com/flarum/flarum/pull/72)

## [0.1.0-beta.15](https://github.com/flarum/flarum/compare/v0.1.0-beta.14...v0.1.0-beta.15)

### Added
- Nicknames added to our bundled list (https://github.com/flarum/flarum/pull/70)

## [0.1.0-beta.14](https://github.com/flarum/flarum/compare/v0.1.0-beta.13...v0.1.0-beta.14)

### Added
- Nginx rules to prevent access to sensitive files (#65)
- IIS configuration added (#66)

### Changed
- Minimum PHP requirement is now 7.2+

### Fixed
- Logo path in readme didn't resolve correctly (#68) 

### Removed
- Social auth drivers removed (#67)

## [0.1.0-beta.13](https://github.com/flarum/flarum/compare/v0.1.0-beta.12...v0.1.0-beta.13)

### Changed
- Prevent access to authorisation tokens saved by composer next to the composer.json file ([adada64](https://github.com/flarum/flarum/commit/adada6456f210ea5c94a805a39d88fa613a9e4a2)).

## [0.1.0-beta.12](https://github.com/flarum/flarum/compare/v0.1.0-beta.8.1...v0.1.0-beta.12)

### Changed
- Consolidate site setup into shared file (#63).

## [0.1.0-beta.8.1](https://github.com/flarum/flarum/compare/v0.1.0-beta.8...v0.1.0-beta.8.1)

### Fixed
- Prevent caching of JSON:API responses ([e2544a2](https://github.com/flarum/flarum/commit/e2544a2a223b8ab2fb9efe00036b755b6e2cd7e7))

# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [0.3.0] - 2016-03-31
### Added
- [#79](https://github.com/tobscure/json-api/pull/79), [#81](https://github.com/tobscure/json-api/pull/81): Allow serializers to add links and metadata to resources. ([@bwaidelich](https://github.com/bwaidelich))

### Changed
- [#62](https://github.com/tobscure/json-api/pull/62): Allow PHPUnit 5.0. ([@vinkla](https://github.com/vinkla))
- [#85](https://github.com/tobscure/json-api/pull/85): Allow creation of relationships without data. ([@josephmcdermott](https://github.com/josephmcdermott))

### Fixed
- [#65](https://github.com/tobscure/json-api/pull/65): Convert snake-case into camelCase when calling a relationship method. ([@avoelpel](https://github.com/avoelpel))
- [#70](https://github.com/tobscure/json-api/pull/70): Include related resources even if relationship is not listed in sparse fieldset. ([@Damith88](https://github.com/Damith88))
- [#72](https://github.com/tobscure/json-api/pull/72): Return `null` in `Parameters::getLimit` if no limit is set. ([@byCedric](https://github.com/byCedric))
- [46142e5](https://github.com/tobscure/json-api/commit/46142e5823da3bebbd9dfc38833af4d808a5e3f3): Prevent primary "data" resources from showing up again in the "included" array. ([@tobscure](https://github.com/tobscure))

## [0.2.1] - 2015-11-02
### Fixed
- Improve performance when working with large numbers of resources

## [0.2.0] - 2015-10-30
Completely rewrite to improve all the things.

- Resources and Collections now contain data and are responsible for serializing it when they are converted to JSON-API output (whereas before serializers were responsible for creating concrete Resources/Collections containing pre-serialized data). Serializers are now only responsible for building attributes and relationships. This is a much more logical/testable workflow, and it makes some really cool syntax possible!
- Support for sparse fieldsets.
- Simplified relationship handling.
- Renamed Criteria to Parameters, add validation.
- Added ErrorHandler for serializing exceptions as JSON-API error documents.
- Updated docs.
- Wrote some tests.
- It should go without saying that this is not at all backwards-compatible with 0.1.

## [0.1.1] - 2015-08-07
### Changed
- Rename abstract serializer

## 0.1.0 - 2015-08-07
- Initial release

[0.3.0]: https://github.com/tobscure/json-api/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/tobscure/json-api/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/tobscure/json-api/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/tobscure/json-api/compare/v0.1.0...v0.1.1

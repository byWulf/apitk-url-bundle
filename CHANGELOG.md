# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.1] - 2021-06-28

### Changed

- `getMainRequest()` util into injectable service including `Request::createFromGlobals()` fallback

## [2.1.0] - 2021-06-25

### Fixed

- Deprecation message with Symfony 5.3 where `getMasterRequest()` is renamed to `getMainRequest()`
- Fixed backwards compatible utilizing the RequestUtil which decides what method to call
- Changed some variable names accordingly

### Updated

- Upgrade to Symfony 5.3
- Updated php cs fixer 3.0

## [2.0.6] - 2021-05-20

### Changed

- Doctrine ManagerRegistry in ResultConverter

## [2.0.5] - 2020-09-21

### Fixed

- Breaking change from 2.0.3 fixed at filter/sort

## [2.0.4] - 2020-09-21

### Fixed

- Breaking change with filter/sort from previous release when used with symfony < 5.1

## [2.0.3] - 2020-08-03

### Fixed

- Deprecations against symfony/http-foundation 5.1

## [2.0.2] - 2020-06-15

### Fixed

- Prevent method `getUniquePlaceholder()` hash from generating strings with a `dot` in it which will break doctrine query builder

## [2.0.1] - 2020-03-30

### Fixed

- Fix `getPaginationLimit()` getter when no pagination was given by the user

## [2.0.0] - 2020-03-20

### Added

- Support for Symfony 5.0
- PHP quality checker to this project

### Changed

- Doctrine ORM to be an optional dependency
- `ApiToolkitRepository` renamed to `ApiToolkitServiceRepository`
- `ApiToolkitDefaultRepository` renamed to `ApiToolkitRepository`

### Removed

- **support for symfony < 4.3**

### Fixed

## [1.0.15] - 2020-03-09

Last release without a changelog ;-)

[2.0.5]: https://github.com/byWulf/apitk-url-bundle/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/byWulf/apitk-url-bundle/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/byWulf/apitk-url-bundle/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/byWulf/apitk-url-bundle/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/byWulf/apitk-url-bundle/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/byWulf/apitk-url-bundle/compare/1.0.15...2.0.0
[1.0.15]: https://github.com/byWulf/apitk-url-bundle/compare/1.0.14...1.0.15

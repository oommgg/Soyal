# Changelog

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

## [2.0.0](https://github.com/oommgg/soyal/compare/v1.6.3...v2.0.0) (2025-12-24)

### ⚠ BREAKING CHANGES

* Requires PHP 8.1 or higher (dropped PHP 7.x and 8.0 support)
* Upgraded nesbot/carbon from v2 to v3

### Features

* **PHP 8.1+**: Add proper type declarations for all class properties ([#upgrade](https://github.com/oommgg/soyal/issues/upgrade))
* **Carbon v3**: Upgrade to nesbot/carbon v3 with full compatibility ([#carbon](https://github.com/oommgg/soyal/issues/carbon))
* **Type Safety**: Add strict type hints to all method parameters and return types
* **Error Handling**: Enhanced error handling with proper exception throwing for all operations
* **Status Parsing**: Improved `getStatus()` to return structured data instead of raw bytes
* **Documentation**: Comprehensive PHPDoc comments with detailed examples for all methods

### Bug Fixes

* **Critical**: Fix checksum validation bug in `check()` method - now correctly processes all data bytes ([#checksum](https://github.com/oommgg/soyal/issues/checksum))
* **Date Parsing**: Add validation for expired dates in `getCard()` to prevent invalid date exceptions
* **Time Parsing**: Add try-catch for `getTime()` and `getOldestLog()` to handle invalid time data
* **Type Safety**: Fix nullable parameter declaration in `resetCards()` for PHP 8.4 compatibility

### Code Quality

* Remove deprecated and commented-out code
* Simplify `check()` checksum calculation logic
* Improve `parseUid()` readability with step-by-step conversion
* Use strict comparison operators (`===`) throughout
* Modernize code style with PHP 8.1+ best practices
* Add comprehensive checksum verification tests

### Documentation

* Complete rewrite of README.md with detailed API documentation
* Add `.github/copilot-instructions.md` for AI coding assistance
* Include protocol details and implementation notes
* Add practical examples for common use cases
* Document all breaking changes and migration path

### [1.6.3](https://github.com/oommgg/soyal/compare/v1.6.2...v1.6.3) (2024-10-29)


### Bug Fixes

* 修正設定卡機時間 ([584c88d](https://github.com/oommgg/soyal/commit/584c88d9ab63e496f0a91ff2dd01d394317955fe))

### [1.6.2](https://github.com/oommgg/soyal/compare/v1.6.1...v1.6.2) (2024-10-28)

### [1.6.1](https://github.com/oommgg/soyal/compare/v1.6.0...v1.6.1) (2024-10-28)

## [1.6.0](https://github.com/oommgg/soyal/compare/v1.5.0...v1.6.0) (2024-10-28)


### Features

* support fingerprint AR-837EF ([a045554](https://github.com/oommgg/soyal/commit/a04555443cd9d4d83bbef862853c7f30ae4d582b))

## [1.5.0](https://github.com/oommgg/Soyal/compare/v1.4.1...v1.5.0) (2023-05-04)


### Features

* update composer.json ([76cac41](https://github.com/oommgg/Soyal/commit/76cac413431c849ffcfca170fdf1a9a9ece7a770))

### [1.4.1](https://github.com/oommgg/Soyal/compare/v1.4.0...v1.4.1) (2023-05-04)

## [1.4.0](https://github.com/oommgg/Soyal/compare/v1.3.0...v1.4.0) (2023-05-04)


### Features

* add support php 7.x & 8.1 ([cf656fd](https://github.com/oommgg/Soyal/commit/cf656fd532710aa728587a7544bb0d6b41cab169))

## [1.3.0](https://github.com/oommgg/Soyal/compare/v1.2.0...v1.3.0) (2023-05-04)


### Features

* add support php 7.x & 8.1 ([15d95a3](https://github.com/oommgg/Soyal/commit/15d95a3ce1298dccb8bf2b1738278fdb83937186))

## [1.2.0](https://github.com/oommgg/Soyal/compare/v1.1.0...v1.2.0) (2019-11-12)


### Features

* **Exception:** custom exception for catching error ([1d0afc3](https://github.com/oommgg/Soyal/commit/1d0afc34caff79da020db7298dec0dd8d4460acd))

## [1.1.0](https://github.com/oommgg/Soyal/compare/v1.0.7...v1.1.0) (2019-08-21)


### Features

* add reboot function ([1a94e59](https://github.com/oommgg/Soyal/commit/1a94e59))

### [1.0.7](https://github.com/oommgg/Soyal/compare/v1.0.6...v1.0.7) (2019-08-21)


### Bug Fixes

* throw excaption when node return nothing ([5f64f03](https://github.com/oommgg/Soyal/commit/5f64f03))

### [1.0.6](https://github.com/oommgg/Soyal/compare/v1.0.5...v1.0.6) (2019-05-12)


### Bug Fixes

* **log:** correct the type ([36a5d50](https://github.com/oommgg/Soyal/commit/36a5d50))



### [1.0.5](https://github.com/oommgg/Soyal/compare/v1.0.4...v1.0.5) (2019-05-12)


### Features

* **Log:** get door & access type, F1 ~ F4 ([5032b62](https://github.com/oommgg/Soyal/commit/5032b62))



# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

# [](https://github.com/oommgg/Soyal/compare/v1.0.3...v) (2019-04-28)


### Bug Fixes

* caught exception ([45f4020](https://github.com/oommgg/Soyal/commit/45f4020))



# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

# [](https://github.com/oommgg/Soyal/compare/v1.0.2...v) (2019-04-28)


### Bug Fixes

* use fsockopen instead socket_* ([2b38113](https://github.com/oommgg/Soyal/commit/2b38113))



# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

# [](https://github.com/oommgg/Soyal/compare/v1.0.1...v) (2019-04-28)


### Features

* add set timeout on connect ([2f17402](https://github.com/oommgg/Soyal/commit/2f17402))



# Change Log

All notable changes to this project will be documented in this file. See [standard-version](https://github.com/conventional-changelog/standard-version) for commit guidelines.

#  (2019-04-26)


### Bug Fixes

* rename paramater, remove unused const ([030d7d4](https://github.com/oommgg/Soyal/commit/030d7d4))

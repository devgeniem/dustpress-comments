# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- DustPress comments partial data to DustPress debugger

### Changed
- Fixed comment depth

## [1.1.13] - 2019-08-02

### Changed
- Changed the `comment_post` action to run with PHP_INT_MAX priority to ensure it gets run last and not preventing important stuff from happening.

## [1.1.12] - 2018-04-06

### Added
- This changelog

### Changed
- Revert back to wp_send_json, since after JSON is sent die is required and the filter is therefore unnecessary.
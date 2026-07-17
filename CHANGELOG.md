# Changelog

All notable changes to `laravel-cadence` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - 2026-07-17

### Added

- Initial stable release.
- Progressive backoff engine for Laravel.
- Exponential backoff strategy.
- Fibonacci backoff strategy.
- Configurable free attempts before backoff is applied.
- Configurable idle timeout for automatic state reset.
- Cache-backed state repository for tracking attempts and lock state.
- Manager and Facade integration for Laravel.
- Support for multiple backoff strategies via configurable drivers.

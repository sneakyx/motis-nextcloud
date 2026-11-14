# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to Semantic Versioning starting with 1.0.0. Until then, 0.x versions may contain breaking changes.

## [Unreleased]

### Added

- Initial Nextcloud app scaffold: app registration (info.xml, routes), page controller, Vue 3 + TypeScript frontend with the Nextcloud design system
- MotisClientFactory that wires the sneakyx/motis-php-client library into the app (configurable MOTIS server, defaults to Transitous)
- PHPUnit unit tests, GitHub Actions CI (Node build + PHP 8.2-8.4 matrix)
- Local development environment via docker-compose (Nextcloud 35, SQLite) and Makefile helpers

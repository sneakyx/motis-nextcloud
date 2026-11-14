# Contributing to Motis for Nextcloud

Thanks for your interest in contributing!

## Getting started

1. Fork / clone the repository.
2. Follow the development setup in the README (Docker based: `make install`, `make build`, `make dev`).
3. Create a feature branch from `main` (e.g. `feature/12-route-search` referencing the issue number).

## Workflow

1. Open or pick an issue; comment that you are working on it.
2. Branch, code, test.
3. Open a pull request against `main` with a clear description referencing the issue ("Closes #...").
4. CI must pass: PHP unit tests and coding standard, frontend lint and build.
5. A maintainer reviews and merges. Do not merge your own pull requests.

## Code conventions

- **All issues, pull requests, commit messages and code comments are in English.**
- PHP: follow the Nextcloud coding standard (`make fix` runs php-cs-fixer with the Nextcloud preset).
- Frontend: Vue 3 with `<script setup lang="ts">`, ESLint and Stylelint with the Nextcloud presets.
- Add or update unit tests for backend changes; line coverage of hand-written backend code should stay at 80 % or above.
- Update `CHANGELOG.md` for user-facing changes.

## Commit messages

Use [Conventional Commits](https://www.conventionalcommits.org/) style, e.g. `feat: add route search UI`, `fix: handle empty geocode results`.

## Reporting bugs

Use the bug report template and include your Nextcloud version, app version and relevant logs.

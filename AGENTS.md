# AGENTS.md - Working conventions for this repository

## Project context

This is **motis-nextcloud**, a Nextcloud app (app id `motis`) that brings MOTIS-powered public transit routing to Nextcloud. It is the second layer of the "Motis for Nextcloud" project (funded by Prototype Fund, #PTFJ02469); the first layer is the Composer package [sneakyx/motis-php-client](https://github.com/sneakyx/motis-php-client).

- License: AGPL-3.0-or-later (Nextcloud App Store requirement). The MIT-licensed motis-php-client dependency is compatible.
- Target: Nextcloud 33-35, PHP >= 8.2. Frontend: Vue 3 + TypeScript + @nextcloud/vue, built with Vite.

## Language

**Everything in this repository is in English**: issues, pull requests, commit messages, code, comments, documentation.

## Identity and workflow

- Commit and push as the bot identity **Lukas Bot** with the email 264617873+lukas-bot-andre@users.noreply.github.com. Never act as the repository owner.
- Workflow: **issue first** -> feature branch `feature/<issue>-<slug>` -> tests -> pull request -> review by the repository owner. **Never merge your own pull requests.**
- Versioning: stay on 0.x.x until the app is published and integration is verified. Keep `appinfo/info.xml` and `package.json` versions in sync.

## Technical rules

- Backend code lives in `lib/` (PSR-4 `OCA\Motis\`), frontend in `src/`
- Built frontend artifacts (`js/`, `css/`) are not committed; build them with `npm run build`.
- All development tooling runs in Docker; see the Makefile targets (`make install`, `make build`, `make test`, `make lint`, `make dev`). The dev Nextcloud instance uses host port **8090** - check for port conflicts before changing it.
- No paid services; CI uses GitHub Actions standard runners only.
- Unit tests use the `nextcloud/ocp` stubs and must not require a running Nextcloud server. Line coverage of hand-written backend code must stay >= 80 %.
- Follow the Nextcloud coding standard (php-cs-fixer), ESLint and Stylelint with the Nextcloud presets (`make fix`).
- `composer.lock` **is** committed in this repository (project type, reproducible builds).
- Generated or vendored code must never be edited manually.

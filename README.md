# Motis for Nextcloud

[![Tests](https://github.com/sneakyx/motis-nextcloud/actions/workflows/tests.yml/badge.svg)](https://github.com/sneakyx/motis-nextcloud/actions/workflows/tests.yml)

**Public transit routing inside Nextcloud** - powered by [MOTIS](https://github.com/motis-project/motis) and the community-run [Transitous](https://transitous.org) service.

Motis plans door-to-door journeys by bus, train, bike and foot directly in Nextcloud:

- **Route search** from A to B (planned)
- **Transit connections to calendar events** - never copy an address to Google Maps again (planned)
- **Transit routes to contact addresses** (planned)

All requests are proxied by your own Nextcloud server through the [sneakyx/motis-php-client](https://github.com/sneakyx/motis-php-client) library, so no addresses or appointment times are sent to tracking companies.

## Status

Early development (0.1.x). The app scaffold is in place; the route search UI and the calendar/contacts integration are the next milestones. See the [issue tracker](https://github.com/sneakyx/motis-nextcloud/issues) for the roadmap.

## Requirements

- Nextcloud 33-35
- PHP >= 8.2
- A MOTIS server to talk to - defaults to the public [Transitous](https://api.transitous.org) instance, any self-hosted MOTIS works

## Development setup

Everything runs in Docker, no local PHP or Node installation is required.

``~~bash
git clone https://github.com/sneakyx/motis-nextcloud.git
cd motis-nextcloud
make install     # composer install + npm ci
make build       # build the Vue frontend
make dev         # start Nextcloud 35 (SQLite) on http://localhost:8090 and enable the app
``~~

Log in as `admin` / `admin`. The dev instance uses host port **8090** to avoid conflicts with other services; check `docker ps` and `ss -tlnp` before changing it.

### If you access the dev instance from another machine

Add your host name or IP to the trusted domains:

``~~bash
docker compose exec -u www-data nextcloud php occ config:system:set trusted_domains 2 --value=<host-or-ip>
``~~

### Tests and linting

``~~bash
make test   # PHPUnit unit tests with coverage
make lint   # PHP lint, coding standard, ESLint, Stylelint
make fix    # auto-fix lint issues
``~~

## Project structure

``~~text
appinfo/            App metadata (info.xml) and routes
lib/                Server-side PHP code (PSR-4: OCA\Motis\)
  AppInfo/          Application entry point
  Controller/       Page controller
  Service/          MotisClientFactory (wires the motis-php-client library)
src/                Vue 3 + TypeScript frontend
templates/          Server-rendered initial page
tests/              PHPUnit unit tests (nextcloud/ocp stubs, no server needed)
``~~

## Architecture

Two layers, as described in the project proposal:

- **Backend (PHP)**: talks to MOTIS through the [sneakyx/motis-php-client](https://packagist.org/packages/sneakyx/motis-php-client) Composer package. The server proxies all MOTIS requests (no CORS issues, admin-configurable MOTIS server, response caching).
- **Frontend (Vue 3)**: built with the Nextcloud design system ([@nextcloud/vue](https://nextcloud-vue-components.netlify.app/)).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Issues and pull requests in English, please.

## License

AGPL-3.0-or-later - see [LICENSE](LICENSE). The library dependency sneakyx/motis-php-client is MIT licensed and compatible with this app's license.

## Funding

Development of this project is part of the "Motis for Nextcloud" project funded within the [Prototype Fund](https://prototypefund.de) program.

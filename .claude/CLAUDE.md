# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Biblioteca is a Symfony 7.1 web application for managing large ebook libraries. It runs inside Docker (MariaDB + Typesense + Apache/PHP 8.3). Nearly all local commands are meant to be run inside the `biblioteca` container — the `justfile` wraps `docker compose run` so that e.g. `just phpunit` executes `composer run test-phpunit` inside the container.

## Commands

All commands below are run through `just` (which `docker compose run`s inside the `biblioteca` service) unless noted.

- `just install` — `composer install`
- `just lint` — runs rector (dry), php-cs-fixer (dry), phpstan
- `just phpcs` — apply php-cs-fixer (NOT dry-run; `just test-phpcs` is dry-run)
- `just rector` — apply rector (NOT dry-run; `composer test-rector` is dry-run)
- `just phpstan` — PHPStan level 9 on `src/` and `tests/`
- `just phpunit` — full PHPUnit run with `XDEBUG_MODE=coverage`. Pass args after `--`, e.g. `just phpunit tests/SampleTest.php` or `just phpunit --filter=SomeTest`
- `just phpunit-xdebug` — PHPUnit with an xdebug debug session (`XDEBUG_TRIGGER=1`)
- `just tests` — runs the full `composer test` pipeline (phpcs, phpstan, rector, phpunit, phpunit-coverage-clover)
- `just console <cmd>` — `bin/console <cmd>` (e.g. `just console doctrine:migrations:migrate`)
- `just console-xdebug <cmd>` — same, with xdebug trigger set
- `just npm <args>` — npm inside the container; frontend build lives at top-level: `npm run dev`, `npm run watch`, `npm run build`

### Test setup

`phpunit.xml.dist` defines three testsuites: `Project Test Suite` (default, excludes Kobo and SmokeTest), `kobo` (everything under `tests/Controller/Kobo` and `tests/Kobo`), and `smoketest` (`tests/SmokeTest.php`). Use `--testsuite=<name>` to target one.

Before running tests the database must be initialized via `composer test-init` (drops+creates test DB, runs migrations, loads fixtures, populates Typesense). The CI workflow in `.github/workflows/tests.yml` is the authoritative reference for a clean test bootstrap.

## Architecture

### Request boundaries / firewalls

Three distinct authentication contexts are wired in `config/packages/security.yaml`:

- `main` — session/form login for the web UI (`App\Entity\User`, `username` property).
- `kobo` — stateless, path `^/kobo`, uses `App\Security\KoboAccessTokenAuthenticator` + `KoboTokenHandler`. Kobo devices authenticate via an access token extracted from the URL.
- `opds` — stateless, path `^/opds/`, Symfony `access_token` with `OpdsTokenHandler` + `OpdsTokenExtractor`.

When adding endpoints, place them under the right prefix: Kobo controllers live in `src/Controller/Kobo/` and OPDS in `src/Controller/OPDS/`. Never assume a session in Kobo/OPDS code paths.

### Kobo integration

The `src/Kobo/` tree is a fairly self-contained subsystem: `Proxy/` relays requests to the real Kobo cloud (toggled by `KOBO_PROXY_ENABLED` / `KOBO_PROXY_USE_EVERYWHERE` env vars), `Request/`/`Response/`/`Serializer/` model the Kobo API contracts, `SyncToken/` and `UpstreamSyncMerger` handle sync state, `Kepubify/` wraps the external `kepubify` binary (path from `KEPUBIFY_BIN`), and `ImageProcessor/` handles cover transforms. `KoboProxyListener` is registered as a high-priority kernel.request listener — it can short-circuit the normal router for proxied Kobo endpoints.

### AI subsystem

`src/Ai/` is built around three swappable abstractions:

- **Communicators** (`src/Ai/Communicator/`) — one class per backend (`OllamaCommunicator`, `OpenAiCommunicator`, `GeminiCommunicator`, `PerplexicaCommunicator`). They all implement `AiCommunicatorInterface` and are auto-tagged `app.ai_communicator`. `CommunicatorDefiner` picks the right one based on the stored `AiModel` entity's `type` (FQCN of the communicator class). In the `test` env the HTTP clients on Ollama and OpenAI communicators are overridden with `App\Tests\Mock\AbstractApiMock` (see `when@test` block in `config/services.yaml`) — do not hit the real APIs from tests.
- **Prompts** (`src/Ai/Prompt/`) — `PromptFactory::getPrompt()` instantiates a `BookPromptInterface` (e.g. `SummaryPrompt`, `TagPrompt`, `SearchHintPrompt`) for a book, resolving the language from the book → user → `'en'` fallback.
- **Context** (`src/Ai/Context/`) — `ContextBuildingInterface` implementations assemble retrieval context (e.g. Perplexica).

AI-driven batch operations are exposed as console commands (`BooksAiCommand`, `BooksAiOrganizeCommand`, `BooksTagsHarmonizeCommand`, `BooksSeriesHarmonizeCommand`, `BooksAuthorsHarmonizeCommand`).

### Filesystem and book layout

`App\Service\BookFileSystemManagerInterface` (impl: `BookFileSystemManager`) owns the on-disk layout — path templates come from `BOOK_FOLDER_NAMING_FORMAT` and `BOOK_FILE_NAMING_FORMAT` env vars (placeholders like `{author}`, `{serie}`, `{title}`). In the `test` env this is aliased to `App\Tests\FileSystemManagerForTests`, which points at `tests/Resources/` — tests that touch book files go through this, never the real public dir. Any code that reads/writes book files should depend on the interface, not the concrete class.

### Search

Typesense is the search backend, integrated via `biblioverse/typesense-bundle`. Index population: `bin/console biblioverse:typesense:populate`. Wrapper services live in `src/Service/Search/`. Embedding config is controlled by `TYPESENSE_EMBED_*` env vars.

### Doctrine

- Entities in `src/Entity/` (auto-mapped), migrations in `migrations/`.
- Gedmo extensions (Tree, Timestampable, Sluggable) are registered as doctrine event listeners in `config/services.yaml`; `Timestampable` uses `Psr\Clock\ClockInterface` which is overridden to `App\Tests\TestClock` in tests so time can be mocked.
- Custom DQL functions registered: `JSON_CONTAINS`, `MONTH`, `YEAR`.
- PHPStan type alias for reading state: `ReadingStateCriteria` (see `phpstan.neon`).

### Async work

`config/packages/messenger.yaml` uses a Doctrine transport for the `async` queue. Mailer and Notifier messages are routed async by default. A long-running worker is needed in prod to process these.

### Config values

`App\Config\ConfigValue` reads `InstanceConfiguration` (DB-stored settings) with env-var fallback. Many parameters in `config/services.yaml` follow the `env(FOO)` default pattern — check there before adding new env vars.

## Conventions

- PHP ≥ 8.3, PHPStan level 9 — expect strict types and fully-typed iterables (the only relaxed rules are `missingType.generics` and the generic `no value type specified in iterable type array` warning).
- Code style: `@Symfony` preset via php-cs-fixer (`.php-cs-fixer.dist.php`) — run `just phpcs` before committing non-trivial changes. Rector is also enforced (`rector.php`).
- New services are autowired/autoconfigured from `src/`; `src/Entity/`, `src/DependencyInjection/`, and `Kernel.php` are excluded.
- Tests that need a deterministic clock should use `App\Tests\TestClock` (public in test env).
- Documentation site is a separate Astro Starlight project under `doc/` (own `package.json`); full user docs live at https://biblioverse.github.io/biblioteca/.

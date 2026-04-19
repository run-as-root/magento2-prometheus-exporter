# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this repo is

A standalone **Magento 2 module** (`RunAsRoot_PrometheusExporter`, composer package `run_as_root/magento2-prometheus-exporter`) that exposes Magento state as Prometheus metrics on a `/metrics` HTTP route. This repo contains only the module — there is no Magento install, no `bin/magento`, no `app/`. Any `bin/magento ...` commands from the README apply to a **consuming** Magento install that has this module in `vendor/`, not to this repo.

## Layout and autoload

Three PSR-4 roots (see `composer.json`):

- `RunAsRoot\PrometheusExporter\` → `src/` — the Magento module (`registration.php` registers it from `src/`)
- `RunAsRoot\NewRelicApi\` → `lib/` — separate internal library for the New Relic metric API (HTTP client, request/response DTOs). Kept as its own namespace so it can be lifted out without dragging in Magento.
- `RunAsRoot\PrometheusExporter\Test\` → `Test/` — split into `Test/Unit/` and `Test/Integration/`.

`build/tests/integration/install-config-mysql.php` is the Magento integration-test install config used by `Test/Integration/`. That's the only reason `build/` still exists.

## Core architecture — how a metric gets produced

Adding or changing metrics almost always means working with this pipeline. All four pieces must line up or the metric won't appear at `/metrics`.

1. **`MetricAggregatorInterface`** (`src/Api/MetricAggregatorInterface.php`) — every metric is a class implementing `aggregate(): bool`, `getCode()`, `getHelp()`, `getType()`. Aggregators compute values and call `UpdateMetricService::update(...)` to persist them.
2. **`MetricAggregatorPool`** (`src/Metric/MetricAggregatorPool.php`) — registry, populated via **`src/etc/di.xml`** under the `items` argument. A new aggregator that isn't listed there will never run. Admin config can additionally disable individual aggregators by code.
3. **`AggregateMetricsCron`** (`src/Cron/AggregateMetricsCron.php`) — runs `* * * * *` under cron group `run_as_root_prometheus_metrics_aggregator` (`src/etc/crontab.xml` + `src/etc/cron_groups.xml`), iterates the pool, writes rows to the `run_as_root_prometheus_metrics` table (schema in `src/etc/db_schema.xml`).
4. **`Controller\Index\Index`** (front route `metrics`, see `src/etc/frontend/routes.xml`) — reads the table via `PrometheusResultFactory` and emits Prometheus exposition format. Optional Bearer-token gate controlled by `Data\Config::getTokenValidationEnabled()`.

Aggregators are grouped by domain under `src/Aggregator/{Category,Cms,CronJob,Customer,Eav,Index,Module,Order,Payment,Product,Shipment,Shipping,Store,User}/`. Follow the existing file in the matching folder as a template; don't invent a new structure.

A parallel, optional pipeline pushes the same metrics to New Relic: `Cron\SendNewRelicMetricsCron` + the `RunAsRoot\NewRelicApi\*` library + the `MetricNewRelicApiProxy` DI preference. This only runs if `newrelic_configuration/cron/cron_interval` is configured.

## Development commands

Run from the repo root unless noted.

```bash
composer install                                    # install deps
vendor/bin/phpunit Test/Unit                        # run unit tests
vendor/bin/phpunit Test/Unit/Aggregator/Order       # single suite/dir
vendor/bin/phpunit --filter OrderCountAggregatorTest  # single test class
vendor/bin/phpstan analyse                          # static analysis (uses phpstan.neon, level 5)
vendor/bin/php-cs-fixer fix                         # apply code style
vendor/bin/php-cs-fixer fix --dry-run --diff        # check style without writing
```

No `phpunit.xml` is checked in — point phpunit at the directory you want to run. Integration tests under `Test/Integration/` require a running Magento environment and the install config in `build/tests/integration/`; they won't run from this repo standalone.

## Code style rules worth knowing

From `.php-cs-fixer.php` (enforced, not suggestions): `@PSR12` + `@Symfony`, `declare(strict_types=1)` required, short array syntax, single quotes, ordered imports (alpha), trailing commas in multiline, `void_return`, `no_superfluous_phpdoc_tags`. Risky rules are enabled — `php-cs-fixer fix` will rewrite code.

`phpstan.neon` is level 5 with a wide ignore list for Magento/Laminas/Symfony/Guzzle/Monolog/Doctrine/Psr classes (these are not autoloadable when analysing the module in isolation). Don't remove those ignores — they exist because this module is analysed without a host Magento install. New errors should be in first-party (`RunAsRoot\*`) code.

Per `~/.claude/CLAUDE.md`: unit tests must be `final` and use `snake_case` method names.

## When editing

- **New metric** → new Aggregator class under the right `src/Aggregator/<Domain>/`, plus an `<item>` entry in `src/etc/di.xml` under `MetricAggregatorPool`. Both are required.
- **New DI preference or type config** → `src/etc/di.xml`. Adminhtml config goes in `src/etc/adminhtml/system.xml`; ACL in `src/etc/acl.xml`.
- **Schema change** → edit `src/etc/db_schema.xml` and regenerate `db_schema_whitelist.json` in a real Magento install (`bin/magento setup:db-declaration:generate-whitelist`), not here.
- **Release** → bump `composer.json` `version`, update `CHANGELOG.md`, tag. The `version` field in composer.json is intentional for this module.

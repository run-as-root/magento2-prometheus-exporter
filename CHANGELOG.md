# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.4.0] - 2026-11-25

### Security

- `/metrics` no longer answers anonymous requests by default (CWE-1188, insecure default). Token authorization (`metric_configuration/security/enable_token`) was opt-in and had no default, so a freshly installed module served every metric - revenue and order figures per store and order state, customer and admin user counts, cron and indexer state - to anybody who could reach the storefront. It now defaults to enabled in `src/etc/config.xml`. Thanks to Łukasz Bajsarowicz for the report.
- A stored "Enable token authorization: No" is removed once during `setup:upgrade`, in every scope (`ResetDisabledTokenAuthorizationDataPatch`). The old configuration form did not say that "No" makes the metrics public, and saving the section for any other reason stored that "No" as well, so without the reset those installations would have stayed open after the update.
- The endpoint fails closed while no token is saved. "Token authorization enabled, token empty" used to compare the `Authorization` header with `Bearer ` followed by an empty string, which matches if the web server passes a trailing space on to PHP. Now every request is rejected until a token has been generated.
- The token is compared with `hash_equals()` (constant time) instead of `!==`.
- `Controller\Adminhtml\Prometheus\GenerateToken`, the action behind the "Generate" button in the configuration, now extends `Magento\Backend\App\Action` and requires the `RunAsRoot_PrometheusExporter::config_runasroot_prometheusexporter` ACL resource. As a plain framework action it was not covered by Magento's admin authentication and returned a random token string to requests without an admin session. Nothing was stored or disclosed by that, but an admin route must not be reachable without a login.

### Changed

- Admin configuration: "Enable token authorization" has a "Use system value" checkbox and explains what switching it off means; the token field states that `/metrics` rejects every request until a token is saved.
- README: the Security section described creating an access token under System → Integrations, which the module has never read. It now documents the module's own token, the opt-out and the upgrade path, and the scrape configuration examples use the token and `scheme: https`.

### Upgrade notes

- **Action required unless Prometheus already scrapes with a token:** after the update `/metrics` answers `401 You are not allowed to see these metrics.` on every installation. Generate a token under Stores → Configuration → run_as_root → Prometheus Metric Configuration → Security Settings and add it to your scrape configuration as `authorization.credentials`. From the command line: `bin/magento config:set metric_configuration/security/token "$(openssl rand -hex 32)"`.
- If you had switched token authorization off on purpose, because `/metrics` is restricted by a firewall or web server allow-list: `setup:upgrade` removes that stored "No" once and logs it as a warning in `var/log/system.log`. Set "Enable token authorization" to "No" again after the update. That choice is kept from then on.
- The reset only covers values stored in the database. Values pinned in `app/etc/config.php`, `app/etc/env.php` or a `CONFIG__` environment variable stay as they are. Verify with `curl -i https://your-magento-store.com/metrics` on every store domain: without a token it has to answer `401`.
- `GenerateToken` now takes a `Magento\Backend\App\Action\Context`, so run `bin/magento setup:di:compile` after updating.

## [4.3.0] - 2026-08-04

### Added

- `IntervalAwareMetricAggregatorInterface`: an optional interface an aggregator can implement to declare a minimum number of seconds between runs. `AggregateMetricsCron` skips such an aggregator until that interval has passed, using the Magento cache to remember the last successful run. Aggregators that do not implement it keep their existing per-cron-tick behaviour, so this is additive for third-party aggregators.

### Fixed

- `OrderAmountAggregator`, `OrderCountAggregator`, `OrderItemAmountAggregator`, `OrderItemCountAggregator`: all four still run an unbounded full-table scan over `sales_order` / `sales_order_item` on every cron tick even after the N+1 fix in 4.2.1, and are the largest contributors to the DB load reported in #69. They now implement `IntervalAwareMetricAggregatorInterface` and are throttled to once every 5 minutes instead of every minute, while every other (cheap) aggregator keeps its existing per-minute schedule.
- `AggregateMetricsCron`: a cache-backend exception while checking/marking an interval-throttled aggregator's last run no longer aborts the rest of that cron tick's aggregator loop, and an aggregator whose `aggregate()` returns `false` (a soft failure) is no longer falsely marked as a successful run - it retries on the next tick instead of being suppressed for 5 minutes.

### Upgrade notes

- `AggregateMetricsCron` gained a `Magento\Framework\App\CacheInterface` constructor argument and is injected as a proxy in `src/etc/di.xml`, so run `bin/magento setup:di:compile` after updating.
- The throttle marker is stored in the Magento cache without tags. `bin/magento cache:clean` does not clear it; `bin/magento cache:flush` does. To force a throttled metric to recompute immediately, use `bin/magento run_as_root:metric:collect --only=<metric_code>`, which ignores the throttle by design.

## [4.2.2] - 2026-07-09

### Fixed

- `IndexerBacklogCountAggregator` / `IndexerChangelogCountAggregator`: skip indexers that are not in Update by Schedule mode, since those never have a `*_cl` changelog table and were erroring on every cron run. `IndexerBacklogCountAggregator` also catches `ChangelogTableNotExistsException` per indexer and logs it instead of aborting the whole aggregation, so one missing table can no longer stop the backlog metric from updating for every other indexer.

## [4.2.1] - 2026-07-09

### Fixed

- `OrderItemCountAggregator` / `OrderItemAmountAggregator`: replaced the per-order N+1 collection loading (full `sales_order` scan plus one order-item collection load per order, every cron minute) with a single streamed query over `sales_order_item`. Item status is derived from the qty columns mirroring `Magento\Sales\Model\Order\Item::getStatusId()`, including children-inherited backorder; emitted series and labels are unchanged. The aggregators no longer unserialize `product_options`, so a corrupt row cannot abort the whole metric anymore.

## [4.1.0] - 2026-05-28

### Changed

- `symfony/console` constraint widened to `^5.4.46|^6.0|^7.0` for Magento 2.4.9 compatibility. (#65)
- Test suite modernised: removed `getMockForAbstractClass()` usage (deprecated in PHPUnit 10+), replaced `returnCallback()` pattern to resolve deprecation notices, and completed mock method definitions. (#65)

## [4.0.0] - 2026-04-20

### Added

- GitHub Actions CI via `graycoreio/github-actions-magento2` against the Mage-OS `supported-version` matrix (Magento 2.4.6-p14 / 2.4.7-p9 / 2.4.8-p4). Unit tests, DI compile, Magento coding standard, and integration tests all run on every push and PR. (#60)
- `phpcs.xml.dist` with the Magento2 coding standard and project-specific exclusions.
- `magento_complex_product_variations_above_recommended_level` (gauge) — count of configurable products with more than 50 variations. (#25)
- `magento_quotes_over_item_limit_count_total` (gauge, `store_code` label) — count of active carts with more than 100 items. (#32)
- `magento_products_with_bad_reviews_count_total` (gauge, `store_code` label) — count of products with `rating_summary < 60`. Guarded by `Magento_Review` module-enabled check. (#26)
- `magento_cache_flush_count_total` (counter) — incremented on every `Magento\Framework\App\Cache\Manager::flush()` invocation via a plugin. (#29)
- `UpdateMetricServiceInterface::increment(string $code, array $labels = []): bool` for counter semantics.

### Changed

- **BREAKING:** minimum PHP requirement is now **8.2** (was 7.4).
- **BREAKING:** `UpdateMetricServiceInterface` gained the `increment()` method — downstream implementers must add it.
- `magento/module-quote` is now a runtime dependency (added to `composer.json` require and `module.xml` sequence).
- `magento/module-review` is now suggested; the bad-reviews aggregator no-ops if the module isn't installed.
- Test suite migrated from phpunit 9.x patterns (`withConsecutive`, `at()`, `onConsecutiveCalls`) to `willReturnCallback` so it runs green on phpunit 9/10/12.
- `composer.json` declares `repositories.mage-os` (public Mage-OS mirror) and `config.allow-plugins` for the Magento composer plugins graycore's coding-standard job pulls in.

### Fixed

- `Test/Integration/Controller/IndexControllerTest` now supplies the `metric_configuration/security/enable_token` fixture so the test actually exercises the token validation code path.
- Several unit tests had stale constructor mocks and mock-expectation drift versus current production signatures; all updated.
- `src/view/adminhtml/templates/system/config/tokenGeneratorButton.phtml`: switched from deprecated `$block->escape*` to `$escaper->escape*` and fixed an unescaped-output XSS sniff.

### Removed

- **BREAKING:** deleted `build/tools/` (legacy GitLab-CI tooling). Closes GHSA-r39x-jcww-82v6 (symfony/process MSYS2 arg escaping).

## [2.0.2] - 2021-06-11

### Fixed

- Reset order count/amount metric before setting them

## [2.0.1] - 2021-05-31

### Fixed

- Fix order count reset

## [2.0.0] - 2020-05-15

### Added

- Added new CLI Command: `run_as_root:metrics:collect`.
- Added `CronJobCountAggregator` to collect metric `magento_cronjob_count_total`.
- Added `BrokenCronJobCountAggregator` to collect metric `magento_cronjob_broken_count_total`.
- Added `IndexerBacklogCountAggregator` to collect metric `magento_indexer_backlog_count_total`.
- Introduced custom cron group `run_as_root_prometheus_metrics_aggregator`.

### Changed

- Added some Interfaces instead of hard file links.
- Changed CLI namespace to `run_as_root`.
- Made all Test Cases final.
- Restructured module directories.
- Updated Readme.

### Removed

- Removed Licenses from PHP Files.

## [1.0.0] - 2019-06-14

### Added

- Basic Module structure
- Metric Collector via Cron
- Console Command to view current metrics
- Cms Aggregator
- Order Aggregator
- Some basic readme
- Prometheus example configuration
- Basic Test Coverage
- StyleCi Integration

## [1.0.1] - 2019-06-14

### Changed

- Fixed composer.json

## [1.0.2] - 2019-06-14

### Removed

- Removed version from composer.json

## [1.1.0] - 2019-06-18

### Added

- CodeCov
- Customer Aggregator
- Additional Unit Tests
- Config wil be evaluated
- Updated Readme

### Changed

- Fixed Metric Format
- Configuration is now Multiselect

## [1.2.0] - 2019-07-10

### Changed

- Interpret all metric aggregators as enabled when config value is NULL


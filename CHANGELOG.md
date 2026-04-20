# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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


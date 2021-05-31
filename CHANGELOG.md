# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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


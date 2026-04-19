# Maintenance Plan — Security, CI, and Open Issues

**Date:** 2026-04-19
**Scope:** Close two open Dependabot alerts, add a real CI pipeline (graycore `check-extension` against Mage-OS), and resolve the five open GitHub issues.

## Context

At time of writing:

- Repo version: `3.2.6`. `composer.json` requires `phpunit/phpunit: ^9.5|^10.0` with no PHP constraint. README claims PHP 7.4+.
- No CI is wired up on GitHub. A `build/tools/composer.json` with Robo + PHPMD + PHPCPD + PDepend + PHPMetrics + `sensiolabs/security-checker` is leftover from the GitLab-CI era — not referenced anywhere in the repo and last touched by "cleanup build" commits.
- Two open Dependabot alerts: one on `phpunit/phpunit <= 12.5.21` (HIGH, argument injection via newline in INI values), one on `symfony/process < 5.4.51` in `build/tools/composer.lock` (MEDIUM, Windows MSYS2 arg escaping — the main `composer.json` already declares `conflict: symfony/process <5.4.46` so runtime is unaffected).
- Five open issues: #24, #25, #26, #29, #32. Issue #24's four metrics are already implemented; the issue is simply stale.

## Sequencing

The six PRs below land in order. Each is green (CI passing) before the next opens, so every change is bisectable.

### PR 1 — `chore: bump phpunit, require PHP 8.2, drop dead build/tools`

Closes Dependabot #2 and #3. Single unified PR because the phpunit bump, the PHP constraint, and the `build/tools/` removal together form the baseline every subsequent PR assumes.

**Changes:**

- Delete `build/tools/` (closes #2 — the vulnerable `symfony/process` only exists in that unused lock file).
- `composer.json`:
  - `require-dev.phpunit/phpunit`: `^9.5|^10.0` → `^12.5.22 || ^13.1.6`.
  - Add `require.php`: `^8.2`.
- Migrate the 27 unit test files and 2 integration test files to phpunit 12+ syntax:
  - Replace `@dataProvider`, `@test`, `@group`, `@covers` annotations with `#[DataProvider(...)]`, `#[Test]`, `#[Group(...)]`, `#[CoversClass(...)]` attributes.
  - Data provider methods return `iterable`/`array` explicitly typed.
  - `setUp(): void` (already the case in the files sampled).
  - Remove `MockObject` aliases that phpunit 12 dropped if used.
- `.php-cs-fixer.php`: no change needed; rules are PHP 8.2-compatible.
- `phpstan.neon`: no change; level 5 stays.
- `README.md`: update "PHP 7.4 or higher" to "PHP 8.2 or higher".

**Acceptance:**

- `vendor/bin/phpunit Test/Unit` green on PHP 8.2, 8.3, 8.4.
- `composer install` succeeds without `build/tools/`.
- `gh api .../dependabot/alerts` returns zero open alerts.

### PR 2 — `ci: add graycore check-extension workflow`

The CI baseline. No new code — just `.github/workflows/ci.yml`. Whatever breaks in the four jobs gets fixed in this same PR; we do not ship a workflow that starts life red.

**Workflow:**

```yaml
name: CI

on:
  push:
    branches: [master]
  pull_request:
    branches: [master]

jobs:
  compute_matrix:
    runs-on: ubuntu-latest
    outputs:
      matrix: ${{ steps.supported-version.outputs.matrix }}
    steps:
      - uses: actions/checkout@v6
      - uses: graycoreio/github-actions-magento2/supported-version@main
        id: supported-version
        with:
          kind: supported          # non-EOL only; today == Mage-OS 2.4.8-p4
          project: mage-os

  check-extension:
    needs: compute_matrix
    uses: graycoreio/github-actions-magento2/.github/workflows/check-extension.yaml@main
    with:
      matrix: ${{ needs.compute_matrix.outputs.matrix }}
      fail-fast: false
    # No secrets block — default magento_repository is https://mirror.mage-os.org/,
    # which needs no auth.
```

**What the four jobs do:**

- `unit-test-extension` — installs Mage-OS, composer-requires this module, runs `Test/Unit/` via Magento's vendored phpunit. Independent of our `require-dev` phpunit.
- `compile-extension` — installs Mage-OS, enables all modules, runs `bin/magento setup:di:compile`. Catches broken DI/type definitions.
- `coding-standard` — installs `magento/magento-coding-standard` + `magento/php-compatibility-fork`, runs `vendor/bin/phpcs . --standard=Magento2`. Expected to surface violations on first run. All violations get fixed in this PR (not deferred).
- `integration_test` — provisions MySQL/OpenSearch/RabbitMQ services, installs Mage-OS, composer-requires our module, runs `Test/Integration/` via the Magento integration test framework.

**Likely fix work inside this PR:**

- Magento PHPCS violations in `src/` and `lib/` (`Magento2.Annotation.*`, `Magento2.Functions.DiscouragedFunction`, missing PHPDoc, etc.).
- Potentially a DI compile failure if any DTO lacks proper constructor arg types under PHP 8.4.
- Integration tests may need updates for the current Magento framework (e.g., `Magento\TestFramework\Helper\Bootstrap` API has been stable, but the `install-config-mysql.php` under `build/tests/integration/` is unused when graycore's workflow drives installation).

**Acceptance:** All four jobs green on the single-row Mage-OS matrix.

### PR 3 — `chore: close #24 as already implemented`

No code change. Adds a comment on issue #24 citing the four aggregators that satisfy it:

- `magento_shipments_count_total` — `src/Aggregator/Shipment/ShipmentCountAggregator.php` (label: `store_code`, `source`)
- `magento_catalog_category_count_total` — `src/Aggregator/Category/CategoryCountAggregator.php` (label: `store_code`, `status`, `menu_status`)
- `magento_website_count_total` — `src/Aggregator/Store/WebsiteCountAggregator.php` (label: none — `store_code` is meaningless on a website-count metric)
- `magento_store_count_total` — `src/Aggregator/Store/StoreCountAggregator.php` (label: `status` — `store_code` is meaningless, every row would be count=1)

Closes #24. No branch needed; just the comment + close action.

### PR 4 — `feat(#25): magento_complex_product_variations_above_recommended_level`

New aggregator that counts configurable products whose child-product count exceeds Magento's 50-variation recommendation.

**Design:**

- Class: `src/Aggregator/Product/ConfigurableProductVariationsAboveRecommendedLevelAggregator.php`.
- Pattern: mirrors the existing `AttributeOptionsAboveRecommendedLevelAggregator` in `src/Aggregator/Eav/`.
- Metric name: `magento_complex_product_variations_above_recommended_level` (matches the issue wording — no `_total` suffix to match the existing `_above_recommended_level_total` aggregator's naming).
- Type: `gauge`.
- Labels: none. Single scalar count.
- Threshold: hardcoded constant `VARIATIONS_THRESHOLD = 50`. YAGNI on admin-configurable until someone asks.
- Query: SQL against `catalog_product_super_link` grouped by `parent_id`, counting groups with >50 rows.
- Wiring: new `<item>` in `src/etc/di.xml` under `MetricAggregatorPool`, alphabetically next to `ProductCountAggregator`.
- Test: `Test/Unit/Aggregator/Product/ConfigurableProductVariationsAboveRecommendedLevelAggregatorTest.php` — mocks `ResourceConnection` and asserts `UpdateMetricService::update` is called with the expected count.

**Acceptance:** New metric appears at `/metrics` with the correct count on a store with >50-variation configurables. CI green.

### PR 5 — `feat(#32): magento_quotes_over_item_limit_count_total`

Counts active quotes (carts) whose item count exceeds Magento's 100-item recommendation.

**Design:**

- Class: `src/Aggregator/Quote/QuotesOverItemLimitCountAggregator.php` (new `Quote/` folder).
- Metric name: `magento_quotes_over_item_limit_count_total`.
- Type: `gauge`.
- Labels: `store_code`.
- Threshold: hardcoded constant `ITEM_LIMIT = 100`.
- Query: `SELECT s.code, COUNT(*) FROM quote q JOIN store s ON s.store_id = q.store_id WHERE q.items_count > 100 AND q.is_active = 1 GROUP BY s.code`.
- Wiring: new `<item>` in `src/etc/di.xml`.
- Composer: adds `magento/module-quote` to `require`.
- Test: `Test/Unit/Aggregator/Quote/QuotesOverItemLimitCountAggregatorTest.php`.

**Acceptance:** New metric appears at `/metrics`. CI green.

### PR 6 — `feat(#29, #26): cache flush counter + bad-reviews gauge`

Two small metrics, bundled because neither is big enough for its own PR and both touch docs/DI/tests in the same shape.

**#29 — `magento_cache_flush_count_total`**

- Type: `counter` (first counter metric that is event-driven instead of cron-aggregated).
- Label: `cache_type` (e.g., `config`, `layout`, `block_html`, `full_page`, ...).
- Source: `Magento\Framework\Event\ObserverInterface` implementation observing the `clean_cache_by_tags` event.
- New class: `src/Observer/IncrementCacheFlushCounterObserver.php`.
- Wiring: new event binding in `src/etc/events.xml` (new file).
- New service method: `UpdateMetricService::increment(string $code, array $labels): void` that does `metric_value = metric_value + 1` on the existing row (or inserts with value 1). This is the first counter-increment pattern in the module, so the service gets a small API addition rather than overloading `update()`.
- Aggregator registration: a no-op `CacheFlushCountAggregator` registered in the pool purely so the metric is listed in admin config (so admins can enable/disable it) and appears in `run_as_root:metrics:show`. Its `aggregate()` returns `true` without doing work — the observer does the writes.
- Test: observer unit test asserting `UpdateMetricService::increment` is called with the right labels for a given event.

**#26 — `magento_products_with_bad_reviews_count_total`**

- Type: `gauge`.
- Labels: `store_code`.
- Definition: a product is "bad" when its most recent `rating_summary < 60` (Magento stores rating_summary 0-100; <60 ≈ below 3 of 5 stars).
- Source: SQL join `review_entity_summary` ← → `store`, aggregated per `store_id`, filtered by `entity_type = 1` (product) and `rating_summary < 60`.
- New class: `src/Aggregator/Review/ProductsWithBadReviewsCountAggregator.php`.
- Composer: adds `magento/module-review` to `require`.
- Guard: aggregator uses `Magento\Framework\Module\Manager::isEnabled('Magento_Review')`; if disabled, returns `true` without writing. This matches the pattern used elsewhere for optional Magento modules.
- Test: unit test mocking `ResourceConnection` and `Manager::isEnabled`.

**Acceptance:** Both metrics appear at `/metrics`. Cache-flush counter increments on `bin/magento cache:flush`. CI green.

## Out of scope

- Replacing `php-cs-fixer` with `magento/magento-coding-standard` as the canonical style tool. The CI `coding-standard` job enforces Magento PHPCS on top of existing php-cs-fixer rules; deciding whether to keep both or drop one is a follow-up.
- Widening the CI matrix to include EOL Mage-OS or Adobe Commerce releases. Requires credentials or sacrificing "no secrets needed" promise.
- Admin-configurable thresholds for any of the new metrics.
- New Relic pipeline changes — the parallel `SendNewRelicMetricsCron` is left alone.

## Rollback

Each PR is a single feature-flag-free commit on master. Rollback is `git revert` per PR. The six PRs have no inter-dependencies beyond the PHP 8.2 constraint landing in PR 1; reverting PR 1 would require reverting everything.

## Follow-ups to file as issues after landing

- PHPCS-vs-php-cs-fixer consolidation decision (choose one).
- Widen CI matrix to a few recent EOL releases if users on 2.4.7 report issues.
- Admin-configurable thresholds for #25 (variations >50) and #32 (items >100) if anyone requests them.

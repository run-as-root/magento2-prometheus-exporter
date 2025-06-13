# Available Metrics

This document lists metrics available in the Magento 2 Prometheus Exporter.

## Metric Categories

### Order Metrics
- Order counts by status
- Revenue metrics
- Order item counts

### Product Metrics
- Product counts by type
- Category metrics
- Inventory levels

### Customer Metrics
- Customer registration counts
- Customer activity

### System Metrics
- Cron job status
- Indexer status
- Cache metrics

### CMS Metrics
- CMS page counts
- CMS block counts

## Metric Collection

Metrics are collected via cron jobs and aggregated for performance.
The `/metrics` endpoint serves data in Prometheus format.

## Aggregator Classes

### ActivePaymentMethodsCountAggregator
**Source**: `src/Aggregator/Payment/ActivePaymentMethodsCountAggregator.php`

### AttributeSetCountAggregator
**Source**: `src/Aggregator/Eav/AttributeSetCountAggregator.php`

### AttributeCountAggregator
**Source**: `src/Aggregator/Eav/AttributeCountAggregator.php`

### ModuleCountAggregator
**Source**: `src/Aggregator/Module/ModuleCountAggregator.php`

### CmsPagesCountAggregator
**Source**: `src/Aggregator/Cms/CmsPagesCountAggregator.php`

### CmsBlockCountAggregator
**Source**: `src/Aggregator/Cms/CmsBlockCountAggregator.php`

### ActiveShippingMethodsCountAggregator
**Source**: `src/Aggregator/Shipping/ActiveShippingMethodsCountAggregator.php`

### OrderCountAggregator
**Source**: `src/Aggregator/Order/OrderCountAggregator.php`

### OrderAmountAggregator
**Source**: `src/Aggregator/Order/OrderAmountAggregator.php`

### OrderItemCountAggregator
**Source**: `src/Aggregator/Order/OrderItemCountAggregator.php`

### OrderItemAmountAggregator
**Source**: `src/Aggregator/Order/OrderItemAmountAggregator.php`

### CronJobCountAggregator
**Source**: `src/Aggregator/CronJob/CronJobCountAggregator.php`

### BrokenCronJobCountAggregator
**Source**: `src/Aggregator/CronJob/BrokenCronJobCountAggregator.php`

### AdminUserCountAggregator
**Source**: `src/Aggregator/User/AdminUserCountAggregator.php`

### ProductCountAggregator
**Source**: `src/Aggregator/Product/ProductCountAggregator.php`

### ProductByTypeCountAggregator
**Source**: `src/Aggregator/Product/ProductByTypeCountAggregator.php`

### ShipmentCountAggregator
**Source**: `src/Aggregator/Shipment/ShipmentCountAggregator.php`

### CustomerGroupCountAggregator
**Source**: `src/Aggregator/Customer/CustomerGroupCountAggregator.php`

### CustomerAddressesCountAggregator
**Source**: `src/Aggregator/Customer/CustomerAddressesCountAggregator.php`

### CustomerCountAggregator
**Source**: `src/Aggregator/Customer/CustomerCountAggregator.php`

### CategoryCountAggregator
**Source**: `src/Aggregator/Category/CategoryCountAggregator.php`

### IndexerChangelogCountAggregator
**Source**: `src/Aggregator/Index/IndexerChangelogCountAggregator.php`

### IndexerBacklogCountAggregator
**Source**: `src/Aggregator/Index/IndexerBacklogCountAggregator.php`

### StoreCountAggregator
**Source**: `src/Aggregator/Store/StoreCountAggregator.php`

### WebsiteCountAggregator
**Source**: `src/Aggregator/Store/WebsiteCountAggregator.php`


*Last updated: Fri Jun 13 14:20:33 UTC 2025*

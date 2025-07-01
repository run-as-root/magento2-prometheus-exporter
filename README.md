# Magento 2 Prometheus Exporter

[![pipeline status](https://gitlab.com/run_as_root/magento2-prometheus-exporter/badges/master/pipeline.svg)](https://gitlab.com/run_as_root/magento2-prometheus-exporter/commits/master)

A comprehensive Magento 2 module that exposes essential Magento metrics in Prometheus format, enabling powerful monitoring and observability for your e-commerce platform.

## 🚀 Features

- **📊 Rich Metrics Collection**: Monitors orders, products, customers, CMS content, cron jobs, indexers, and more
- **🔧 Configurable Metrics**: Enable/disable specific metrics through Magento admin interface
- **🔐 Secure Access**: Bearer token authentication support to protect your metrics endpoint
- **⚡ Performance Optimized**: Efficient cron-based data aggregation to minimize performance impact
- **🎯 Prometheus Ready**: Native Prometheus format output for seamless integration
- **🔌 Extensible Architecture**: Easy to add custom metrics with clean interfaces

## 📋 Table of Contents

- [Installation](#-installation)
- [Configuration](#-configuration)
- [Prometheus Setup](#-prometheus-setup)
- [Available Metrics](#-available-metrics)
- [Security](#-security)
- [Custom Metrics](#-custom-metrics)
- [CLI Commands](#-cli-commands)
- [Architecture](#-architecture)
- [Troubleshooting](#-troubleshooting)
- [Contributing](#-contributing)
- [License](#-license)

## 📦 Installation

### Requirements

- Magento 2.3.x or higher
- PHP 7.4 or higher
- Composer
- Secure dependency versions (see Security section)

### Install via Composer

```bash
composer require run_as_root/magento2-prometheus-exporter
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento cache:flush
```

### Verify Installation

After installation, your metrics endpoint will be available at:
```
https://your-magento-store.com/metrics
```

## ⚙️ Configuration

### Admin Configuration

Navigate to **Stores → Configuration → Prometheus → Metric Configuration** to:

- Enable/disable specific metric collectors
- Configure authentication settings
- Set collection intervals
- Manage metric labels and groupings

### Cron Configuration

The module automatically registers a cron job that runs every minute to aggregate metrics. The job uses a dedicated cron group: `run_as_root_prometheus_metrics_aggregator`.

## 🎯 Prometheus Setup

### Basic Configuration

Add the following scrape configuration to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'magento-2'
    scrape_interval: 5m
    scrape_timeout: 60s
    metrics_path: /metrics
    static_configs:
      - targets: 
          - 'your-magento-store.com'
```

### With Authentication

For production environments, secure your metrics endpoint:

```yaml
scrape_configs:
  - job_name: 'magento-2'
    scrape_interval: 5m
    scrape_timeout: 60s
    metrics_path: /metrics
    authorization:
      type: 'Bearer'
      credentials: 'your-bearer-token-here'
    static_configs:
      - targets: 
          - 'your-magento-store.com'
```

### Advanced Configuration with Labels

```yaml
scrape_configs:
  - job_name: 'magento-2'
    scrape_interval: 5m
    scrape_timeout: 60s
    metrics_path: /metrics
    authorization:
      type: 'Bearer'
      credentials: 'your-bearer-token-here'
    static_configs:
      - targets: 
          - 'your-magento-store.com'
    relabel_configs:
      - source_labels: [__address__]
        target_label: instance
        replacement: 'magento-production'
```

## 📊 Available Metrics

### Order Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_orders_count_total` | gauge | `status`, `store_code` | Total count of orders by status and store |
| `magento_orders_amount_total` | gauge | `status`, `store_code` | Total revenue by order status and store |
| `magento_order_items_count_total` | gauge | `status`, `store_code` | Total count of order items |

### Product & Catalog Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_products_by_type_count_total` | gauge | `product_type` | Count of products by type (simple, configurable, etc.) |
| `magento_catalog_category_count_total` | gauge | `status`, `menu_status`, `store_code` | Count of categories by status |

### EAV & Attribute Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_eav_attribute_count_total` | gauge | - | Total count of EAV attributes |
| `magento_eav_attribute_options_above_recommended_level_total` | gauge | - | Count of attributes with more than 100 options (performance risk) |

### Customer Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_customer_count_total` | gauge | `store_code` | Total count of registered customers |

### Content Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_cms_block_count_total` | gauge | `store_code` | Count of CMS blocks |
| `magento_cms_page_count_total` | gauge | `store_code` | Count of CMS pages |

### System Health Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_cronjob_count_total` | gauge | `status`, `job_code` | Count of cron jobs by status |
| `magento_cronjob_broken_count_total` | gauge | - | Count of broken cron jobs |
| `magento_indexer_backlog_count_total` | gauge | `title` | Count of items in indexer backlog |



### Infrastructure Metrics
| Metric | Type | Labels | Description |
|--------|------|--------|-------------|
| `magento_store_count_total` | gauge | `status` | Count of stores by status |
| `magento_website_count_total` | gauge | - | Total count of websites |
| `magento_shipments_count_total` | counter | `source`, `store_code` | Count of shipments created |

## 🔐 Security

### Authentication Setup

1. **Generate Bearer Token**: Navigate to Magento Admin → System → Integrations
2. **Create New Integration**: Set up API access with appropriate permissions
3. **Configure Token**: Use the generated access token in your Prometheus configuration

### Best Practices

- Always use HTTPS for metrics endpoints in production
- Regularly rotate authentication tokens
- Restrict access to metrics endpoint via firewall rules
- Monitor access logs for unusual activity
- Keep dependencies updated to address security vulnerabilities
- Use specific version constraints in composer.json to prevent vulnerable packages

### IP Whitelisting

Consider restricting access to your Prometheus server IPs:

```nginx
location /metrics {
    allow 10.0.0.0/8;     # Your Prometheus server
    allow 172.16.0.0/12;  # Internal network
    deny all;
    try_files $uri $uri/ /index.php?$args;
}
```

### Dependency Security

This module includes security fixes for vulnerable dependencies:

- **GuzzleHTTP**: Minimum version 7.4.5 (fixes CVE-2022-31043)
- **Monolog**: Minimum version 2.9.0 (fixes log injection vulnerabilities)
- **Symfony Console**: Minimum version 5.4.46 (fixes XSS vulnerabilities)
- **Changed stability**: From "dev" to "stable" for production safety

Regular security updates are applied via composer constraints and conflict declarations.

## 🔧 Custom Metrics

### Creating a Custom Metric Aggregator

1. **Implement the Interface**:

```php
<?php
namespace YourNamespace\YourModule\Aggregator;

use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CustomMetricAggregator implements MetricAggregatorInterface
{
    private UpdateMetricService $updateMetricService;

    public function __construct(UpdateMetricService $updateMetricService)
    {
        $this->updateMetricService = $updateMetricService;
    }

    public function aggregate(): void
    {
        // Your metric collection logic here
        $value = $this->calculateCustomMetric();
        
        $this->updateMetricService->update(
            'your_custom_metric_total',
            (string) $value,
            'gauge',
            'Description of your custom metric',
            ['label1' => 'value1', 'label2' => 'value2']
        );
    }

    private function calculateCustomMetric(): int
    {
        // Implement your metric calculation
        return 42;
    }
}
```

2. **Register in DI Configuration** (`etc/di.xml`):

```xml
<type name="RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool">
    <arguments>
        <argument name="items" xsi:type="array">
            <item name="YourCustomAggregator" xsi:type="object">YourNamespace\YourModule\Aggregator\CustomMetricAggregator</item>
        </argument>
    </arguments>
</type>
```

### Example: Product Rating Aggregator

```php
<?php
namespace YourNamespace\YourModule\Aggregator;

use Magento\Review\Model\ResourceModel\Review\CollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ProductRatingAggregator implements MetricAggregatorInterface
{
    private CollectionFactory $reviewCollectionFactory;
    private UpdateMetricService $updateMetricService;

    public function __construct(
        CollectionFactory $reviewCollectionFactory,
        UpdateMetricService $updateMetricService
    ) {
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->updateMetricService = $updateMetricService;
    }

    public function aggregate(): void
    {
        $collection = $this->reviewCollectionFactory->create()
            ->addStatusFilter(\Magento\Review\Model\Review::STATUS_APPROVED);

        $averageRating = $collection->getConnection()
            ->fetchOne('SELECT AVG(rating_value) FROM rating_option_vote');

        $this->updateMetricService->update(
            'magento_product_average_rating',
            (string) round($averageRating, 2),
            'gauge',
            'Average product rating across all approved reviews'
        );
    }
}
```

## 💻 CLI Commands

### Collect Metrics Manually

```bash
php bin/magento run_as_root:metrics:collect
```

### View Current Metrics

```bash
php bin/magento run_as_root:metrics:show
```

### Clear Metrics Data

```bash
php bin/magento run_as_root:metrics:clear
```

## 🏗️ Architecture

### Data Flow

1. **Cron Execution**: Every minute, the `run_as_root_prometheus_metrics_aggregator` cron group executes
2. **Metric Aggregation**: Each enabled aggregator collects and processes data
3. **Data Storage**: Aggregated metrics are stored in `run_as_root_prometheus_metrics` table
4. **Endpoint Response**: `/metrics` controller serves data in Prometheus format

### Key Components

- **MetricAggregatorInterface**: Contract for all metric collectors
- **MetricAggregatorPool**: Registry of all available aggregators
- **UpdateMetricService**: Service for storing metric data
- **PrometheusController**: HTTP endpoint for serving metrics

### Database Schema

The module creates a dedicated table `run_as_root_prometheus_metrics` with the following structure:

- `metric_id`: Primary key
- `metric_code`: Unique metric identifier
- `metric_value`: Numeric value
- `metric_type`: Type (gauge, counter, histogram)
- `metric_help`: Description text
- `metric_labels`: JSON-encoded labels
- `updated_at`: Last update timestamp

## 🔍 Troubleshooting

### Common Issues

#### Metrics Not Updating
```bash
# Check cron status
php bin/magento cron:status

# Run metrics collection manually
php bin/magento run_as_root:metrics:collect

# Check system logs
tail -f var/log/system.log | grep prometheus
```

#### Permission Denied on /metrics
- Verify web server has read access to Magento files
- Check Magento file permissions: `find . -type f -exec chmod 644 {} \;`
- Ensure proper ownership: `chown -R www-data:www-data .`

#### High Memory Usage
- Reduce collection frequency in cron configuration
- Disable unused metric aggregators in admin configuration
- Consider implementing metric data retention policies

### Debug Mode

Enable debug logging by adding to `app/etc/env.php`:

```php
'system' => [
    'default' => [
        'prometheus_exporter' => [
            'debug' => '1'
        ]
    ]
]
```

### Performance Monitoring

Monitor the impact of metric collection:

```bash
# Check metric collection execution time
grep "prometheus.*aggregator" var/log/system.log

# Monitor database table size
SELECT COUNT(*) as total_metrics, 
       MAX(updated_at) as last_update 
FROM run_as_root_prometheus_metrics;
```

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://gitlab.com/your-username/magento2-prometheus-exporter.git`
3. Install dependencies: `composer install`
4. Create a feature branch: `git checkout -b feature/your-feature-name`

### Code Standards

- Follow Magento 2 coding standards
- Write unit tests for new features
- Update documentation for new metrics or features
- Use meaningful commit messages

### Pull Request Process

1. Ensure all tests pass: `vendor/bin/phpunit`
2. Update the CHANGELOG.md
3. Submit a pull request with a clear description
4. Respond to code review feedback

### Reporting Issues

Please include:
- Magento version
- PHP version
- Module version
- Detailed error messages
- Steps to reproduce

## 📝 Changelog

### Recent Updates

- **🛡️ Security Fix**: Updated dependency constraints to prevent vulnerable package versions
- **📊 New Metric**: Added `magento_eav_attribute_options_above_recommended_level_total` to track catalog health
- **⚡ Performance**: Improved metric collection efficiency with optimized SQL queries
- **🔒 Stability**: Changed minimum-stability from "dev" to "stable" for production safety

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

- **run_as_root GmbH** - Original development and maintenance
- **Prometheus Community** - For the excellent monitoring toolkit
- **Magento Community** - For feedback and contributions

## 📞 Support

- **Email**: info@run-as-root.sh
- **GitLab Issues**: [Report a bug](https://gitlab.com/run_as_root/magento2-prometheus-exporter/issues)
- **Documentation**: [Wiki](https://gitlab.com/run_as_root/magento2-prometheus-exporter/wiki)

---

**Made with ❤️ by [run_as_root](https://www.run-as-root.sh)**
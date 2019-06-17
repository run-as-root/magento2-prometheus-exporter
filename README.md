# Magento 2 Prometheus Exporter

![Branch stable](https://img.shields.io/badge/stable%20branch-master-blue.svg)
![Branch Develop](https://img.shields.io/badge/dev%20branch-develop-blue.svg)
![](https://github.styleci.io/repos/191891355/shield)
[![codecov](https://codecov.io/gh/run-as-root/magento2-prometheus-exporter/branch/master/graph/badge.svg)](https://codecov.io/gh/run-as-root/magento2-prometheus-exporter)

This Magento 2 Module exposes a new route under /metrics with Magento 2 specific metrics in the format of [prometheus](https://prometheus.io).
The different metrics are grouped into modules and can be enabled/disabled via the Magento Backend.

## Installation

Install the Module via composer by running: 

```
composer require run-as-root/magento2-prometheus-exporter
php bin/magento setup:upgrade
```

## Prometheus Configuration

After installing the Magento Module, your Prometheus needs to get pointed to your Magento Metrics endpoint. To do so, add the following lines to your
prometheus.yml under scrape_configs: 

```yaml
- job_name: 'Magento 2 Exporter'
  scrape_interval: 5m
  scrape_timeout: 60s
  metrics_path: /metrics
  static_configs:
  - targets: 
    - your-magento-url
```

## Module functionality

The module registers a cron job that runs every minute. The cronjob is
responsible for aggregating the metric data. The aggregated data is
stored in the table `run_as_root_prometheus_metrics`. The added
controller collects the data stored in the table and renders the correct
response for prometheus.

## Metrics

The following metrics will be collected:

| Metric | Labels | TYPE | Help | 
| --- | --- | --- | --- |
| magento_orders_count_total | status | gauge | All Magento Orders |
| magento_orders_amount_total | status | gauge | Total amount of all Magento Orders |
| magento_order_items_count_total | status | gauge | Total count of orderitems |
| magento_cms_block_count_total | | gauge | Total count of available cms blocks |
| magento_cms_page_count_total | | gauge | Total count of available cms pages |

## Add you own Metric

To add a new metric, you need to implement the `\RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface`. The metric 
aggregator object is responsible for collecting the necessary information for the specific metric from magento and then
add a new metric record. New records can be easily added via `\RunAsRoot\PrometheusExporter\Service\UpdateMetricService`.

In addition to the implementation of the MetricAggregatorInterface, you have to add your specific Aggregator to the 
`MetricAggregatorPool` defined in the `di.xml`. For example:

```xml
<type name="RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool">
        <arguments>
            <argument name="items" xsi:type="array">
                <item name="OrderAmountAggregator" xsi:type="object">RunAsRoot\PrometheusExporter\Aggregator\Order\OrderAmountAggregator</item>
                <item name="OrderCountAggregator" xsi:type="object">RunAsRoot\PrometheusExporter\Aggregator\Order\OrderCountAggregator</item>
                <item name="OrderItemAmountAggregator" xsi:type="object">RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemAmountAggregator</item>
                <item name="OrderItemCountAggregator" xsi:type="object">RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemCountAggregator</item>
            </argument>
        </arguments>
    </type>
``` 

 

## Contribution

If you have something to contribute, weither it's a feature, a feature request, an issue or something else, feel free to. There are no contribution guidelines.

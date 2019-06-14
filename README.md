# Magento 2 Prometheus Exporter

![Branch stable](https://img.shields.io/badge/stable%20branch-master-blue.svg)
![Branch Develop](https://img.shields.io/badge/dev%20branch-develop-blue.svg)

This Magento 2 Module exposes a new route under /metrics with Magento 2 specific metrics in the format of [prometheus](https://prometheus.io).
The different metrics are grouped into modules and can be enabled/disabled via the Magento Backend.

# Metrics

The following metrics will be collected:

| Metric | Labels | TYPE | Help | 
| --- | --- | --- | --- |
| magento_orders_count_total | status | gauge | All Magento Orders |
| magento_orders_amount_total | status | gauge | Total amount of all Magento Orders |
| magento_order_items_count_total | status | gauge | Total count of orderitems |

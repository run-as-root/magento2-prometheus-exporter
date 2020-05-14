<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Console\Command;

use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Cron\AggregateMetricsCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table as ConsoleTableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CollectMetricsCommand extends Command
{
    private const COMMAND_NAME = 'run_as_root:metric:collect';
    private const COMMAND_DESCRIPTION = 'Starts all active aggregator to collect metrics for prometheus.';

    private $aggregateMetricsCron;

    public function __construct(AggregateMetricsCron $aggregateMetricsCron)
    {
        parent::__construct();

        $this->aggregateMetricsCron = $aggregateMetricsCron;
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESCRIPTION);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->aggregateMetricsCron->execute();
    }
}

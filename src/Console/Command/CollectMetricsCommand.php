<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Console\Command;

use RunAsRoot\PrometheusExporter\Cron\AggregateMetricsCron;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
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
        $this->addOption('only', 'o', InputOption::VALUE_OPTIONAL, 'Run a specific metric by name. Run run_as_root:metrics:list to get a list of all available metrics. Metric has to be enabled in the System Config.');

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $onlyOption = $input->getOption('only');
        $output->write($this->aggregateMetricsCron->executeOnly($onlyOption), true);
    }
}

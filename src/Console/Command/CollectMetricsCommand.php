<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Console\Command;

use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
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

    private State $state;

    /**
     * @param AggregateMetricsCron $aggregateMetricsCron
     * @param State $state
     */
    public function __construct(
        AggregateMetricsCron $aggregateMetricsCron,
        State $state
    ) {
        parent::__construct();

        $this->aggregateMetricsCron = $aggregateMetricsCron;
        $this->state = $state;
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
        try {
            $this->state->getAreaCode();
        } catch (LocalizedException $exception) {
            $this->state->setAreaCode(Area::AREA_CRONTAB);
        }

        $onlyOption = $input->getOption('only');

        if ($onlyOption) {
            $output->write($this->aggregateMetricsCron->executeOnly($onlyOption), true);
        } else {
            $this->aggregateMetricsCron->execute();
        }

        return Cli::RETURN_SUCCESS;
    }
}

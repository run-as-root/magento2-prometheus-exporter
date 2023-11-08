<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Console\Command;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table as ConsoleTableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMetricsCommand extends Command
{
    private const COMMAND_NAME = 'run_as_root:metric:list';
    private const COMMAND_DESCRIPTION = 'Lists all metrics currently collected in the db.';

    private MetricRepositoryInterface $metricRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private State $state;

    public function __construct(
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $state
    ) {
        parent::__construct();

        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->state = $state;
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESCRIPTION);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->state->setAreaCode(Area::AREA_GLOBAL);
        } catch (LocalizedException $e) {
            $output->writeln('<error>Cannot set area to global<error/>');
        }
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->metricRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() === 0) {
            $output->writeln('No metrics found');

            return Command::SUCCESS;
        }

        /** @var MetricInterface[] $metrics */
        $metrics = $searchResults->getItems();

        $table = new ConsoleTableHelper($output);
        $table->setHeaders([ 'id', 'code', 'labels', 'value' ]);

        foreach ($metrics as $metric) {
            $table->addRow($metric->asArray());
        }

        $table->render();

        return Command::SUCCESS;
    }
}

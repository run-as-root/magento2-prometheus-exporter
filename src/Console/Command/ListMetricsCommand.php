<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Console\Command;

use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table as ConsoleTableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMetricsCommand extends Command
{
    private const COMMAND_NAME = 'rar_prometheus:metric:list';
    private const COMMAND_DESCRIPTION = 'Check Project Status at Eurotext. Will update project status in Magento.';

    /**
     * @var MetricRepositoryInterface
     */
    private $metricRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct();

        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME);
        $this->setDescription(self::COMMAND_DESCRIPTION);

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResults = $this->metricRepository->getList($searchCriteria);

        if ($searchResults->getTotalCount() === 0) {
            $output->writeln('No metrics found');

            return;
        }

        /** @var MetricInterface[] $metrics */
        $metrics = $searchResults->getItems();

        $table = new ConsoleTableHelper($output);
        $table->setHeaders(['id', 'code', 'labels', 'value']);
        foreach ($metrics as $metric) {
            $table->addRow($metric->asArray());
        }

        $table->render();
    }
}

<?php declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Category;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\EntityManager\MetadataPool;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CategoryCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_catalog_category_count_total';
    private const CATEGORY_ENTITY_ID = 3;

    private UpdateMetricService $updateMetricService;

    private ResourceConnection $resourceConnection;

    private MetadataPool $metadataPool;

    private ExpressionFactory $expressionFactory;

    /**
     * @param UpdateMetricService $updateMetricService
     * @param ResourceConnection $resourceConnection
     * @param MetadataPool $metadataPool
     * @param ExpressionFactory $expressionFactory
     */
    public function __construct(
        UpdateMetricService $updateMetricService,
        ResourceConnection  $resourceConnection,
        MetadataPool        $metadataPool,
        ExpressionFactory   $expressionFactory
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
        $this->metadataPool = $metadataPool;
        $this->expressionFactory = $expressionFactory;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Categories count by status and store code.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $data = $connection->fetchAll($this->getSelect($connection));

        foreach ($data as $datum) {
            $labels = ['store_code' => $datum['STORE_CODE']];

            $this->updateMetrics(
                (string)$datum['ACTIVE_IN_MENU'],
                array_merge($labels, ['status' => 'enabled', 'menu_status' => 'enabled'])
            );
            $this->updateMetrics(
                (string)$datum['ACTIVE_NOT_IN_MENU'],
                array_merge($labels, ['status' => 'enabled', 'menu_status' => 'disabled'])
            );
            $this->updateMetrics(
                (string)$datum['NOT_ACTIVE_IN_MENU'],
                array_merge($labels, ['status' => 'disabled', 'menu_status' => 'enabled'])
            );
            $this->updateMetrics(
                (string)$datum['NOT_ACTIVE_NOT_IN_MENU'],
                array_merge($labels, ['status' => 'disabled', 'menu_status' => 'disabled'])
            );
        }

        return true;
    }

    private function updateMetrics(string $count, array $labels): void
    {
        $this->updateMetricService->update(self::METRIC_CODE, $count, $labels);
    }

    /**
     * SQL example:
     * select s.code,
     * COUNT( (IF (ccei1.value IS NULL, ccei2.value, ccei1.value) AND IF (ccei3.value IS NULL, ccei4.value,
     * ccei3.value)) or null) as active_in_menu, COUNT( (IF (ccei1.value IS NULL, ccei2.value, ccei1.value) AND NOT IF
     * (ccei3.value IS NULL, ccei4.value, ccei3.value)) or null) as active_not_in_menu, COUNT( (NOT IF (ccei1.value IS
     * NULL, ccei2.value, ccei1.value) AND IF (ccei3.value IS NULL, ccei4.value, ccei3.value)) or null) as
     * disabled_in_menu, COUNT( (NOT IF (ccei1.value IS NULL, ccei2.value, ccei1.value) AND NOT IF (ccei3.value IS
     * NULL, ccei4.value, ccei3.value)) or null) as disabled_not_in_menu from store_group sg inner join store s on
     * s.group_id = sg.group_id inner join catalog_category_entity cce1 on sg.root_category_id = cce1.entity_id inner
     * join catalog_category_entity cce2 on cce2.path like CONCAT(cce1.path, '%') left join catalog_category_entity_int
     * ccei1 on ccei1.entity_id = cce2.entity_id and ccei1.attribute_id = 32 and ccei1.store_id = s.store_id left join
     * catalog_category_entity_int ccei2 on ccei2.entity_id = cce2.entity_id and ccei2.attribute_id = 32 and
     * ccei2.store_id = 0 left join catalog_category_entity_int ccei3 on ccei3.entity_id = cce2.entity_id and ccei3.attribute_id = 601 and ccei3.store_id = s.store_id left join catalog_category_entity_int ccei4 on ccei4.entity_id = cce2.entity_id and ccei4.attribute_id = 601 and ccei4.store_id = 0 group by s.code;
     *
     *
     * @param AdapterInterface $connection
     *
     * @return Select
     * @throws \Exception
     */
    private function getSelect(AdapterInterface $connection): Select
    {
        $linkField = $this->metadataPool->getMetadata(CategoryInterface::class)->getLinkField();
        $isActive = $this->getIsActiveAttributeId($connection);
        $isInMenu = $this->getIsInMenuAttributeId($connection);
        $expression = 'COUNT((
            %s IF(ccei1.value IS NULL, ccei2.value, ccei1.value) AND
            %s IF(ccei3.value IS NULL, ccei4.value, ccei3.value)
        ) or null)';

        $activeInMenu = $this->expressionFactory->create(['expression' => sprintf($expression, '', '')]);
        $activeNotInMenu = $this->expressionFactory->create(['expression' => sprintf($expression, '', 'NOT')]);
        $notActiveInMenu = $this->expressionFactory->create(['expression' => sprintf($expression, 'NOT', '')]);
        $notActiveNotInMenu = $this->expressionFactory->create(
            ['expression' => sprintf($expression, 'NOT', 'NOT')]
        );

        $select = $connection->select();

        $select->from(['sg' => $connection->getTableName('store_group')])
               ->joinInner(
                   ['s' => $connection->getTableName('store')],
                   'sg.group_id = s.group_id'
               )->joinInner(
                    ['cce1' => $connection->getTableName('catalog_category_entity')],
                    'sg.root_category_id = cce1.entity_id'
                )->joinInner(
                ['cce2' => $connection->getTableName('catalog_category_entity')],
                "cce2.path like CONCAT(cce1.path, '%')"
                )->joinLeft(
                    ['ccei1' => $connection->getTableName('catalog_category_entity_int')],
                    "cce2.$linkField = ccei1.$linkField AND " .
                    "ccei1.attribute_id = $isActive AND ccei1.store_id = s.store_id"
                )->joinLeft(
                    ['ccei2' => $connection->getTableName('catalog_category_entity_int')],
                    "cce2.$linkField = ccei2.$linkField AND " .
                    "ccei2.attribute_id = $isActive AND ccei2.store_id = 0"
                )->joinLeft(
                    ['ccei3' => $connection->getTableName('catalog_category_entity_int')],
                    "cce2.$linkField = ccei3.$linkField AND " .
                    "ccei3.attribute_id = $isInMenu AND ccei3.store_id = s.store_id"
                )->joinLeft(
                    ['ccei4' => $connection->getTableName('catalog_category_entity_int')],
                    "cce2.$linkField = ccei4.$linkField AND " .
                    "ccei4.attribute_id = $isInMenu AND ccei4.store_id = 0"
                )->reset(Select::COLUMNS)->columns(
                   [
                       'STORE_CODE' => 's.code',
                       'ACTIVE_IN_MENU' => $activeInMenu,
                       'ACTIVE_NOT_IN_MENU' => $activeNotInMenu,
                       'NOT_ACTIVE_IN_MENU' => $notActiveInMenu,
                       'NOT_ACTIVE_NOT_IN_MENU' => $notActiveNotInMenu
                   ]
               )->group('s.code');

        return $select;
    }

    private function getIsActiveAttributeId(AdapterInterface $connection): int
    {
        return $this->getAttributeId($connection, 'is_active');
    }

    private function getIsInMenuAttributeId(AdapterInterface $connection): int
    {
        return $this->getAttributeId($connection, 'include_in_menu');
    }

    private function getAttributeId(AdapterInterface $connection, string $code): int
    {
        $select = $connection->select();

        $select->from($connection->getTableName('eav_attribute'))
               ->where('entity_type_id = ?', self::CATEGORY_ENTITY_ID)
               ->where('attribute_code = ?', $code)
               ->reset(Select::COLUMNS)
               ->columns(['attribute_id']);

        return (int)$connection->fetchOne($select);
    }
}

<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Integration\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Setup\Patch\Data\ResetDisabledTokenAuthorizationDataPatch;
use RunAsRoot\PrometheusExporter\Test\Integration\IntegrationTestAbstract;

final class ResetDisabledTokenAuthorizationDataPatchTest extends IntegrationTestAbstract
{
    private const ENABLE_TOKEN_PATH = 'metric_configuration/security/enable_token';
    private const TOKEN_PATH = 'metric_configuration/security/token';

    /**
     * @magentoDbIsolation enabled
     */
    public function testItRemovesStoredOffValuesInEveryScopeAndKeepsEverythingElse(): void
    {
        /** @var ResourceConnection $resource */
        $resource = $this->objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $table = $resource->getTableName('core_config_data');

        $connection->delete($table, ['path LIKE ?' => 'metric_configuration/security/%']);
        $connection->insertMultiple(
            $table,
            [
                ['scope' => 'default', 'scope_id' => 0, 'path' => self::ENABLE_TOKEN_PATH, 'value' => '0'],
                ['scope' => 'websites', 'scope_id' => 1, 'path' => self::ENABLE_TOKEN_PATH, 'value' => ''],
                ['scope' => 'websites', 'scope_id' => 2, 'path' => self::ENABLE_TOKEN_PATH, 'value' => null],
                ['scope' => 'stores', 'scope_id' => 1, 'path' => self::ENABLE_TOKEN_PATH, 'value' => '1'],
                ['scope' => 'default', 'scope_id' => 0, 'path' => self::TOKEN_PATH, 'value' => 'keep-this-token'],
            ]
        );

        $this->objectManager->create(ResetDisabledTokenAuthorizationDataPatch::class)->apply();

        $remaining = $connection->fetchAll(
            $connection->select()
                ->from($table, ['scope', 'scope_id', 'path', 'value'])
                ->where('path LIKE ?', 'metric_configuration/security/%')
                ->order(['path ASC', 'scope ASC', 'scope_id ASC'])
        );

        $this->assertEquals(
            [
                ['scope' => 'stores', 'scope_id' => 1, 'path' => self::ENABLE_TOKEN_PATH, 'value' => '1'],
                ['scope' => 'default', 'scope_id' => 0, 'path' => self::TOKEN_PATH, 'value' => 'keep-this-token'],
            ],
            $remaining
        );
    }
}

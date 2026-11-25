<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Psr\Log\LoggerInterface;

/**
 * Removes a stored "Enable token authorization: No" once, in every scope, so that the secure default applies.
 *
 * Up to 4.3.0 token authorization for /metrics was opt-in and the admin form did not say that "No" publishes the
 * metrics to everybody. Saving the configuration section for any other reason stored that "No" as well, so a stored
 * value cannot be told apart from a deliberate decision. Whoever wants anonymous access switches token authorization
 * off again after the update; that choice is kept, because a patch is applied only once.
 */
class ResetDisabledTokenAuthorizationDataPatch implements DataPatchInterface
{
    private const CONFIG_PATH = 'metric_configuration/security/enable_token';

    public function __construct(
        private readonly ModuleDataSetupInterface $moduleDataSetup,
        private readonly LoggerInterface $logger
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    public function apply(): self
    {
        $removed = (int) $this->moduleDataSetup->getConnection()->delete(
            $this->moduleDataSetup->getTable('core_config_data'),
            [
                'path = ?' => self::CONFIG_PATH,
                // Every value that ScopeConfigInterface::isSetFlag() reads as "off".
                "value IS NULL OR value IN ('', '0')",
            ]
        );

        if ($removed > 0) {
            $this->logger->warning(
                sprintf(
                    'RunAsRoot_PrometheusExporter: removed %d stored "Enable token authorization: No" value(s). '
                    . '/metrics now requires a Bearer token. If anonymous access is intended, switch token '
                    . 'authorization off again under Stores > Configuration > run_as_root > '
                    . 'Prometheus Metric Configuration.',
                    $removed
                )
            );
        }

        return $this;
    }
}

<?php

use Mwltr\Robo\Magento2\loadMagentoTasks;

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 *
 * @SuppressWarnings(PHPMD)
 */
class RoboFile extends \Robo\Tasks
{
    use loadMagentoTasks;

    public const COMPOSER_BIN = 'www/vendor/bin/composer';
    public const PHPUNIT_BIN = 'www/vendor/bin/phpunit';
    public const PHPMD_BIN = './build/tools/bin/phpmd';
    public const PHPSTAN_BIN = './build/tools/bin/phpstan';
    public const PHPMETRICS_BIN = './build/tools/bin/phpmetrics';
    public const PHPCPD_BIN = './build/tools/bin/phpcpd';
    public const PHPCS_BIN = './build/tools/bin/phpcs';
    public const PDEPEND_BIN = './build/tools/bin/pdepend';

    /** @var \stdClass */
    private $config;

    public function __construct()
    {
        $this->config = (object)[
            'srcDirs' => ['src'],
        ];
    }

    public function ciUnitTests(): void
    {
        $this->stopOnFail();

        $this->runUnitTests(['coverage' => true, 'debug' => true, 'testdox' => false]);
        $this->runPhpStan();
        $this->runPhpMetrics();
        $this->runPhpMd();
        $this->runPhpCpd();
        $this->runPhpCs();
        $this->runPdepend();
    }

    public function ciIntegrationTests(string $dbHost, string $dbUser, string $dbPassword, string $dbName): void
    {
        $this->stopOnFail();

        $this->magentoSetupInstall($dbHost, $dbUser, $dbPassword, $dbName);
        $this->magentoSetupUpgrade();
        $this->runIntegrationTests(
            $dbHost, $dbUser, $dbPassword, $dbName,
            ['coverage' => true, 'debug' => true, 'testdox' => false]
        );
    }

    public function runUnitTests(array $opts = ['debug' => false, 'coverage|c' => false, 'testdox' => false]): void
    {
        $t = $this->taskPhpUnit(self::PHPUNIT_BIN);
        $t->configFile('phpunit.xml.dist');

        if ($opts['debug']) {
            $t->debug();
        }

        if ($opts['coverage']) {
            $t->option('log-junit', 'build/output/phpunit/junit.xml');
            $t->option('coverage-clover', 'build/output/phpunit/clover.xml');
            $t->option('coverage-html', 'build/output/phpunit/coverage-html');
            $t->option('coverage-text');
        }

        if ($opts['testdox']) {
            $t->option('testdox');
        }
        $t->option('colors', 'never', '=');

        $t->run();
    }

    public function runIntegrationTests(
        string $dbHost,
        string $dbUser,
        string $dbPassword,
        string $dbName,
        array $opts = ['debug' => false, 'coverage|c' => false, 'testdox' => false]
    ): void {
        $buildTestsDir = 'build/tests/integration';
        $wwwTestsDir = 'www/dev/tests/integration';

        // Copy install-config-mysql and replace db-settings
        $fileContent = file_get_contents("$buildTestsDir/install-config-mysql.php");
        $fileContent = str_replace(
            ['$DB_HOST', '$DB_NAME', '$DB_USER', '$DB_PASSWORD',],
            [$dbHost, $dbName, $dbUser, $dbPassword],
            $fileContent
        );
        file_put_contents("$wwwTestsDir/etc/install-config-mysql.php", $fileContent);

        // Copy config-global
        $this->_copy("$wwwTestsDir/etc/config-global.php.dist", "$wwwTestsDir/etc/config-global.php");

        // Run Tests
        $cwd = getcwd();
        $t = $this->taskPhpUnit($cwd . '/' . self::PHPUNIT_BIN);
        $t->dir($wwwTestsDir);
        $t->configFile("$cwd/phpunit-integration.xml.dist");

        if ($opts['debug']) {
            $t->debug();
        }

        if ($opts['coverage']) {
            $t->option('log-junit', $cwd . '/build/output/phpunit-integration/junit.xml');
            $t->option('coverage-clover', $cwd . '/build/output/phpunit-integration/clover.xml');
            $t->option('coverage-html', $cwd . '/build/output/phpunit-integration/coverage-html');
            $t->option('coverage-text');
        }

        if ($opts['testdox']) {
            $t->option('testdox');
        }

        $t->run();
    }

    public function runPhpStan(): void
    {
        $PHPSTAN_BIN = self::PHPSTAN_BIN;
        $outputDir = 'build/output/phpstan';
        $outputTable = "$outputDir/phpstan.table.txt";
        $dirs = $this->getAnalyzeDirs();
        $cmd = "$PHPSTAN_BIN  analyse -c phpstan.neon --no-progress -l max --error-format=table $dirs | tee $outputTable";

        $this->_mkdir($outputDir);
        $this->_exec($cmd);
    }

    public function runPhpMetrics(): void
    {
        $PHPMETRICSBIN = self::PHPMETRICS_BIN;
        $reportHtml = 'build/output/phpmetrics';
        $dirs = $this->getAnalyzeDirs();
        $cmd = "$PHPMETRICSBIN --report-html=$reportHtml $dirs";

        $this->_exec($cmd);
    }

    public function runPhpCs(): void
    {
        $PHPCSBIN = self::PHPCS_BIN;
        $outputDir = 'build/output/phpcs';
        $outputFile = $outputDir . '/phpcs.txt';
        $dirs = $this->getAnalyzeDirs();
        $cmd = "php $PHPCSBIN --standard=build/tools/vendor/magento-ecg/coding-standard/EcgM2/ --report-full --report-source $dirs | tee $outputFile";

        $this->_mkdir($outputDir);
        $this->_exec($cmd);
    }

    public function runPhpMd(): void
    {
        $phpMdBin = self::PHPMD_BIN;
        $rulesets = 'cleancode,codesize,controversial,design,naming,unusedcode';
        $outputDir = 'build/output/phpmd';
        $outputFile = $outputDir . '/phpmd.html';
        $dirs = $this->getAnalyzeDirs(',');
        $cmd = "php $phpMdBin $dirs html $rulesets --exclude=Test > $outputFile";

        $this->stopOnFail(false);

        $this->_mkdir($outputDir);
        $this->_exec($cmd);

        $this->stopOnFail(true);
    }

    public function runPhpCpd(): void
    {
        $PHPCPDBIN = self::PHPCPD_BIN;
        $outputDir = 'build/output/phpcpd';
        $logFile = $outputDir . '/phpcpd.xml';
        $dirs = $this->getAnalyzeDirs();
        $options = '--regexps-exclude="#.*Test.*#"';
        $cmd = "$PHPCPDBIN $options --log-pmd=$logFile $dirs";

        $this->_mkdir($outputDir);
        $this->_exec($cmd);
    }

    public function runPdepend(): void
    {
        $pdependBin = self::PDEPEND_BIN;
        $outputDir = 'build/output/pdepend';
        $pyramidFile = $outputDir . '/overview-pyramid.svg';
        $jdependChart = $outputDir . '/jdepend-chart.svg';
        $dirs = $this->getAnalyzeDirs(',');
        $cmd = "$pdependBin --overview-pyramid=$pyramidFile --jdepend-chart=$jdependChart $dirs";

        $this->_mkdir($outputDir);
        $this->_exec($cmd);
    }

    public function magentoSetupInstall(string $dbHost, string $dbUser, string $dbPassword, string $dbName): void
    {
        $options = [
            'db-host' => $dbHost,
            'db-name' => $dbName,
            'db-password' => $dbPassword,
            'db-user' => $dbUser,
            'admin-email' => 'admin@mwltr.de',
            'admin-firstname' => 'Admin',
            'admin-lastname' => 'Admin',
            'admin-password' => 'admin123',
            'admin-user' => 'admin',
            'backend-frontname' => 'admin',
            'base-url' => 'http://rar.m2-prometheus.test',
            'currency' => 'EUR',
            'language' => 'en_US',
            'session-save' => 'files',
            'timezone' => 'Europe/Berlin',
            'use-rewrites' => '1',
            'use-secure' => '0',
            'use-secure-admin' => '0',
        ];

        // Install Magento2
        $hasEnvPhp = is_file('www/app/etc/env.php');
        if (!$hasEnvPhp) {
            $install = $this->taskMagentoSetupInstallTask();
            $install->options($options);
            $install->dir('www');
            $install->run();
        }
    }

    public function magentoSetupUpgrade(): void
    {
        $upgrade = $this->taskMagentoSetupUpgradeTask();
        $upgrade->dir('www');
        $upgrade->run();
    }

    private function getAnalyzeDirs(string $glue = ' '): string
    {
        return implode($glue, $this->config->srcDirs);
    }
}

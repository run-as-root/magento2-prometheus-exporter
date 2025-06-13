<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit;

use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testBasicSetup(): void
    {
        $this->assertTrue(true, 'Basic test setup works');
    }

    public function testDirectoryStructure(): void
    {
        $this->assertDirectoryExists(__DIR__ . '/../../src');
        $this->assertDirectoryExists(__DIR__ . '/../../lib');
    }

    public function testComposerJsonExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../composer.json');
    }

    public function testRegistrationFileExists(): void
    {
        $this->assertFileExists(__DIR__ . '/../../registration.php');
    }

    public function testSourceDirectoryNotEmpty(): void
    {
        $srcDir = __DIR__ . '/../../src';
        $files = glob($srcDir . '/**/*.php', GLOB_BRACE);
        $this->assertGreaterThan(0, count($files), 'Source directory should contain PHP files');
    }

    public function testLibDirectoryNotEmpty(): void
    {
        $libDir = __DIR__ . '/../../lib';
        $files = glob($libDir . '/**/*.php', GLOB_BRACE);
        $this->assertGreaterThan(0, count($files), 'Lib directory should contain PHP files');
    }

    public function testPhpVersionCompatibility(): void
    {
        $this->assertGreaterThanOrEqual(70400, PHP_VERSION_ID, 'PHP version should be at least 7.4');
    }
}

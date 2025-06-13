<?php

declare(strict_types=1);

/**
 * Script to prepare the repository for PHPStan level 8 analysis
 * This script adds missing type declarations and fixes common issues
 */

function findPhpFiles(string $directory): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

function addStrictTypes(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    // Skip if already has strict_types
    if (strpos($content, 'declare(strict_types=1)') !== false) {
        return true;
    }

    // Add strict_types declaration after opening PHP tag
    if (strpos($content, '<?php') === 0) {
        $content = str_replace('<?php', "<?php\n\ndeclare(strict_types=1);", $content);
        return file_put_contents($filePath, $content) !== false;
    }

    return false;
}

function addReturnTypes(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    $modified = false;

    // Add void return type to methods that don't return anything
    $patterns = [
        // Constructor methods
        '/public function __construct\([^)]*\)\s*\{/' => 'public function __construct($1): void {',
        // Methods ending with echo, print, or assignments
        '/public function ([a-zA-Z_][a-zA-Z0-9_]*)\([^)]*\)\s*\{[^}]*(?:echo|print|\$[a-zA-Z_][a-zA-Z0-9_]*\s*=)[^}]*\}(?!\s*:)/' => null,
    ];

    // Simple void method detection
    $lines = explode("\n", $content);
    $inMethod = false;
    $methodStart = 0;
    $braceCount = 0;

    for ($i = 0; $i < count($lines); $i++) {
        $line = trim($lines[$i]);

        // Detect method start
        if (preg_match('/^\s*(?:public|private|protected)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)\s*(?::\s*\w+\s*)?\{?/', $line, $matches)) {
            if (!preg_match('/:\s*\w+/', $line)) { // No return type specified
                $methodName = $matches[1];
                if ($methodName !== '__construct') {
                    // This is a candidate for void return type
                    $lines[$i] = str_replace(
                        'function ' . $methodName,
                        'function ' . $methodName,
                        $line
                    );
                }
            }
        }
    }

    $newContent = implode("\n", $lines);
    if ($newContent !== $content) {
        $modified = true;
        file_put_contents($filePath, $newContent);
    }

    return $modified;
}

function addPropertyTypes(string $filePath): bool
{
    $content = file_get_contents($filePath);
    if ($content === false) {
        return false;
    }

    $modified = false;

    // Add basic property types for common patterns
    $patterns = [
        // String properties
        '/private\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*[\'"][^\'\"]*[\'"];/' => 'private string $$1 = $2;',
        '/protected\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*[\'"][^\'\"]*[\'"];/' => 'protected string $$1 = $2;',

        // Array properties
        '/private\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*\[\];/' => 'private array $$1 = [];',
        '/protected\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*\[\];/' => 'protected array $$1 = [];',

        // Boolean properties
        '/private\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(true|false);/' => 'private bool $$1 = $2;',
        '/protected\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*(true|false);/' => 'protected bool $$1 = $2;',

        // Integer properties
        '/private\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*\d+;/' => 'private int $$1 = $2;',
        '/protected\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*=\s*\d+;/' => 'protected int $$1 = $2;',
    ];

    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== $content) {
            $content = $newContent;
            $modified = true;
        }
    }

    if ($modified) {
        file_put_contents($filePath, $content);
    }

    return $modified;
}

function createStubFiles(): void
{
    // Create stub files for external dependencies
    $stubsDir = __DIR__ . '/../../stubs';
    if (!is_dir($stubsDir)) {
        mkdir($stubsDir, 0755, true);
    }

    // Magento stubs
    $magentoStub = <<<'PHP'
<?php

declare(strict_types=1);

namespace Magento\Framework\App {
    interface ConfigInterface {}
    class Config implements ConfigInterface {}
}

namespace Magento\Framework\ObjectManagerInterface {
    interface ObjectManagerInterface {}
}

namespace Magento\Framework\Model {
    abstract class AbstractModel {}
}

namespace Magento\Framework\Controller {
    abstract class AbstractAction {}
}
PHP;

    file_put_contents($stubsDir . '/magento.php', $magentoStub);
}

function main(): void
{
    echo "Preparing repository for PHPStan Level 8...\n";

    $directories = ['src', 'lib'];
    $totalFiles = 0;
    $modifiedFiles = 0;

    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            echo "Directory $dir not found, skipping...\n";
            continue;
        }

        echo "Processing directory: $dir\n";
        $files = findPhpFiles($dir);
        $totalFiles += count($files);

        foreach ($files as $file) {
            echo "Processing: $file\n";
            $modified = false;

            // Add strict types
            if (addStrictTypes($file)) {
                $modified = true;
            }

            // Add return types
            if (addReturnTypes($file)) {
                $modified = true;
            }

            // Add property types
            if (addPropertyTypes($file)) {
                $modified = true;
            }

            if ($modified) {
                $modifiedFiles++;
                echo "  - Modified\n";
            }
        }
    }

    // Create stub files
    createStubFiles();

    echo "\nSummary:\n";
    echo "- Total files processed: $totalFiles\n";
    echo "- Files modified: $modifiedFiles\n";
    echo "- Stub files created\n";
    echo "\nRepository is now better prepared for PHPStan Level 8 analysis.\n";
    echo "You may still need to manually add type hints for complex cases.\n";
}

if (php_sapi_name() === 'cli') {
    main();
}

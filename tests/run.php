<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$coverageTool = trim((string)getenv('PHP_COVERAGE_COMMAND'));
$coverageOutput = trim((string)getenv('PHP_COVERAGE_OUTPUT_DIR'));
if ($coverageTool !== '' && $coverageOutput !== '' && getenv('ADBQ_COVERAGE_CHILD') !== '1') {
    if (!is_file($coverageTool) || !is_executable($coverageTool) || !is_dir($coverageOutput)) {
        throw new RuntimeException('Die konfigurierte Coverage-Umgebung ist nicht ausführbar.');
    }
    $wrapper = rtrim($coverageOutput, '/') . '/adbqplanung-tests.php';
    $report = rtrim($coverageOutput, '/') . '/adbqplanung-tests.xml';
    $wrapperCode = "<?php\nputenv('ADBQ_COVERAGE_CHILD=1');\nrequire "
        . var_export(__FILE__, true) . ";\n";
    if (file_put_contents($wrapper, $wrapperCode) === false) {
        throw new RuntimeException('Der temporäre Coverage-Wrapper konnte nicht geschrieben werden.');
    }
    $command = implode(' ', array_map('escapeshellarg', [
        $coverageTool,
        'execute',
        '--clover',
        $report,
        '--include',
        dirname(__DIR__) . '/lib',
        '--add-uncovered',
        $wrapper,
    ]));
    passthru($command, $exitCode);
    exit($exitCode);
}

foreach (glob(__DIR__ . '/*Test.php') ?: [] as $testFile) {
    require $testFile;
}

\AdBqPlanning\Tests\TestRunner::finish();

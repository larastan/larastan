<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

use function exec;
use function implode;
use function sprintf;

use const PHP_BINARY;

class FormRequestCommandLineTest extends TestCase
{
    /**
     * A real run strips method bodies from files outside the analysed paths, which the test
     * harness never does. A request class is such a file whenever only the code using it is analysed.
     */
    public function testInfersRequestsOutsideTheAnalysedPaths(): void
    {
        exec(sprintf(
            '%s %s analyse --configuration=%s --level=0 --no-progress --error-format=raw %s 2>&1',
            PHP_BINARY,
            __DIR__ . '/../../vendor/bin/phpstan',
            __DIR__ . '/../Type/data/config-with-migrations.neon',
            __DIR__ . '/data/form-request-command-line.php',
        ), $output, $exitCode);

        $this->assertSame(0, $exitCode, implode("\n", $output));
    }
}

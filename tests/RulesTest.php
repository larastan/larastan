<?php

declare(strict_types=1);

namespace Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

/**
 * @deprecated Please extend from PHPStan\Testing\RuleTestCase instead
 */
abstract class RulesTest extends BaseTestCase
{
    use ExecutesLarastan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configPath = __DIR__.'/phpstan-tests.neon';
    }

    /**
     * Returns an array of errors that were found after analyzing $filename.
     *
     * @param  string  $filename
     * @return array
     */
    protected function findErrors(string $filename): array
    {
        return $this->execLarastan($filename)['files'][$filename] ?? [];
    }

    /**
     * Returns an associative Collection where each key represents the line
     * number and the value represents the error found. Will return
     * at most one error per line.
     *
     * @param  string  $filename
     * @return array<int, string>
     */
    protected function findErrorsByLine(string $filename): array
    {
        $errors = $this->findErrors(realpath($filename));

        return collect($errors['messages'] ?? [])->mapWithKeys(function ($message) {
            return [$message['line'] => $message['message']];
        })->toArray();
    }

    /**
     * Tests whether the expected errors were found in a particular order
     * after analyzing $filename.
     *
     * @param  string  $filename
     * @param  array  $expected
     */
    protected function assertSeeErrorsInOrder(string $filename, array $expected): void
    {
        $errors = array_values($this->findErrorsByLine($filename));
        $this->assertEquals($expected, $errors);
    }
}

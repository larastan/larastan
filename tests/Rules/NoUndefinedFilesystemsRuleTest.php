<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoUndefinedFilesystemsRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoUndefinedFilesystemsRule> */
class NoUndefinedFilesystemsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoUndefinedFilesystemsRule($this->createReflectionProvider());
    }

    public function testNoFalsePositives(): void
    {
        $this->analyse([__DIR__ . '/data/CorrectFilesystem.php'], []);
    }

    public function testStorageDisk(): void
    {
        $this->analyse([__DIR__ . '/data/UndefinedFilesystem.php'], [
            ["Called 'Storage::disk()' with an undefined filesystem disk.", 13],
        ]);
    }
}

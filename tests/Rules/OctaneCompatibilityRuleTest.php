<?php

declare(strict_types=1);

namespace Tests\Rules;

use Tests\RulesTest;

class OctaneCompatibilityRuleTest extends RulesTest
{
    public function testNoContainerInjection(): void
    {
        $errors = $this->setConfigPath(__DIR__.'/Data/octane.neon')->findErrorsByLine(__DIR__.'/Data/ContainerInjection.php');

        $this->assertEquals([
            12 => 'Consider using bind method instead or pass a closure.',
            16 => 'Consider using bind method instead or pass a closure.',
            25 => 'Consider using bind method instead or pass a closure.',
            29 => 'Consider using bind method instead or pass a closure.',
            33 => 'Consider using bind method instead or pass a closure.',
            46 => 'Consider using bind method instead or pass a closure.',
            51 => 'Consider using bind method instead or pass a closure.',
        ], $errors);
    }
}

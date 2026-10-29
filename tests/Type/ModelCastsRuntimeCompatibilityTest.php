<?php

declare(strict_types=1);

namespace Tests\Type;

use ModelCastsParsing\TraitAliasedCastsModel;
use PHPUnit\Framework\TestCase;

final class ModelCastsRuntimeCompatibilityTest extends TestCase
{
    public function testTraitMethodAliasedAsCastsMatchesLaravelRuntime(): void
    {
        $model = new TraitAliasedCastsModel();

        self::assertSame('bool', $model->getCasts()['integer']);
    }

    public function testCastsMethodOverridesOlderPropertyEntry(): void
    {
        // Laravel executes casts() at initialization; Larastan does not.
        $model = new DynamicCastPrecedenceModel();

        self::assertSame('string', $model->getCasts()['integer']);
    }

    public function testExplicitPrimaryKeyCastAppliesWithoutMigrationSchema(): void
    {
        $model = new DynamicCastPrecedenceModel();
        $model->setRawAttributes(['id' => 123]);

        self::assertSame('123', $model->getAttribute('id'));
    }

    public function testExplicitStringCastWinsOverAutomaticTimestampHandling(): void
    {
        $model = new DynamicCastPrecedenceModel();
        $model->setRawAttributes(['created_at' => '2026-01-01 12:34:56']);

        self::assertSame('2026-01-01 12:34:56', $model->getAttribute('created_at'));
    }
}

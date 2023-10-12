<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Testing\TypeInferenceTestCase;

class GeneralTypeTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__.'/data/request-object.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/eloquent-builder.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/query-builder.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/paginator-extension.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model-properties.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model-properties-relations.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/route.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/conditionable.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/tappable.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/translate.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model-factories.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/environment-helper.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/view.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/bug-1346.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/request-header.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/optional-helper.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/abstract-manager.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/facades.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/where-relation.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/validator.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/form-request.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/database-transaction.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/container-array-access.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/view-exists.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/application-make.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/container-make.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/abort.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/throw.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/app-make.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/auth.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/custom-eloquent-builder.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/carbon.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/contracts.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/higher-order-collection-proxy-methods.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model-relations.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/model-scopes.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/date-extension.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/gate-facade.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/helpers.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/custom-eloquent-collection.php');
        yield from self::gatherAssertTypes(__DIR__.'/data/translator.php');

        //##############################################################################################################

        // Console Commands
        yield from self::gatherAssertTypes(__DIR__.'/../application/app/Console/Commands/FooCommand.php');
        yield from self::gatherAssertTypes(__DIR__.'/../application/app/Console/Commands/BarCommand.php');
        yield from self::gatherAssertTypes(__DIR__.'/../application/app/Console/Commands/BazCommand.php');
    }

    /**
     * @dataProvider dataFileAsserts
     */
    public function testFileAsserts(
        string $assertType,
        string $file,
        ...$args
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__.'/data/config-with-migrations.neon'];
    }
}

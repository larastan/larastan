<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Testing\TypeInferenceTestCase;

class GeneralTypeTest extends TypeInferenceTestCase
{
    /**
     * @return iterable<mixed>
     */
    public function dataFileAsserts(): iterable
    {
        yield from $this->gatherAssertTypes(__DIR__.'/data/request-object.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/eloquent-builder.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/paginator-extension.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/model-properties.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/model-properties-relations.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/route.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/conditionable.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/translate.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/model-factories.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/environment-helper.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/view.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/bug-1346.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/request-header.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/optional-helper.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/abstract-manager.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/facades.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/where-relation.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/validator.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/form-request.php');
        yield from $this->gatherAssertTypes(__DIR__.'/data/database-transaction.php');
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

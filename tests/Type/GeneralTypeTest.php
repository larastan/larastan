<?php

declare(strict_types=1);

namespace Type;

use PHPStan\Testing\TypeInferenceTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionFunction;

use function Orchestra\Testbench\laravel_version_compare;
use function str_contains;

class GeneralTypeTest extends TypeInferenceTestCase
{
    /** @return iterable<mixed> */
    public static function dataFileAsserts(): iterable
    {
        yield from self::gatherAssertTypes(__DIR__ . '/data/abort.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/abstract-manager.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/app-make.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/application-make.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/arr-get-pull.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/arr-map.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/arrayable.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/auth.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/benchmark.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/belongs-to-many-pivot-accessor.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/builder-of-type.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1346.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1565.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1718.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1760.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1830.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1985.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-2057.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-2073.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-2111.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-2188.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-2283.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/conditionable.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/container-array-access.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/container-make.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/contracts.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/custom-eloquent-builder.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/custom-eloquent-collection.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/database-transaction.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/eager-loading-callbacks.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/collection-of-type.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/soft-deletes-static-builder.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/date-extension.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/eloquent-builder.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/eloquent-getter-types.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/eloquent-where.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/environment-helper.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/event-dispatcher.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/facade-fakes.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/facades.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/form-request.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/gate-facade.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/has-events.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/helpers.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/higher-order-collection-proxy-methods.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-date-column-casts.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-factories.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-methods.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-serialization.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-properties-relations.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-properties.php');

        if (laravel_version_compare('13.2.0', '>=')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/model-properties-l13-2.php');
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/model-relations.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/migrator.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-scopes.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-template-class-string.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/model-with-relations.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/optional-helper.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/paginator-extension.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/passthru.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/query-builder.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/relationship-query-callbacks.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/relation-of-type.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/request-header.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/request-object.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/route.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/tappable.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/throw.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/translate.php');

        // Laravel master still reports version 13 while using the concrete translator return type.
        if (str_contains((string) (new ReflectionFunction('trans'))->getReturnType(), 'Illuminate\\Translation\\Translator')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/translate-concrete.php');
        } else {
            yield from self::gatherAssertTypes(__DIR__ . '/data/translate-contract.php');
        }

        yield from self::gatherAssertTypes(__DIR__ . '/data/translator.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/validator.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/view-exists.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/view.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/where-relation.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1997.php');
        yield from self::gatherAssertTypes(__DIR__ . '/data/bug-1819.php');

        if (laravel_version_compare('13.4.0', '>=')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/model-collections-l13-4.php');
        }

        if (laravel_version_compare('13.31.0', '>=')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/eloquent-builder-l13-31.php');
        }

        if (laravel_version_compare('13.30.1', '>=')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/passthru-l13-30-1.php');
        }

        if (laravel_version_compare('11.42.0', '>=') && laravel_version_compare('12.0.0', '<')) {
            yield from self::gatherAssertTypes(__DIR__ . '/data/facades-l11-42.php');
        }

        //##############################################################################################################

        // Console Commands
        yield from self::gatherAssertTypes(__DIR__ . '/../application/app/Console/Commands/BarCommand.php');
        yield from self::gatherAssertTypes(__DIR__ . '/../application/app/Console/Commands/BazCommand.php');
        yield from self::gatherAssertTypes(__DIR__ . '/../application/app/Console/Commands/FooCommand.php');
        yield from self::gatherAssertTypes(__DIR__ . '/../application/app/Console/Commands/RegisteredInKernelCommand.php');
        yield from self::gatherAssertTypes(__DIR__ . '/../application/app/Console/LoadedCommands/LoadedByKernelCommand.php');
    }

    #[DataProvider('dataFileAsserts')]
    public function testFileAsserts(
        string $assertType,
        string $file,
        mixed ...$args,
    ): void {
        $this->assertFileAsserts($assertType, $file, ...$args);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/data/config-with-migrations.neon'];
    }
}

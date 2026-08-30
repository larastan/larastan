<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\MemberWithCustomKey;
use Larastan\Larastan\Properties\ModelCastHelper;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use function class_exists;

#[CoversClass(ModelCastHelper::class)]
class ModelCastHelperTest extends PHPStanTestCase
{
    private ReflectionProvider $reflectionProvider;

    private Parser $parser;

    public function setUp(): void
    {
        if (! class_exists('Illuminate\Database\Eloquent\Attributes\Table')) {
            $this->markTestSkipped('Eloquent PHP attributes require Laravel 13+.');
        }

        $this->reflectionProvider = $this->createReflectionProvider();
        $this->parser             = self::getContainer()->getService('currentPhpVersionSimpleDirectParser');
    }

    #[Test]
    public function it_uses_custom_primary_key_from_table_php_attribute_when_resolving_casts(): void
    {
        $modelCastHelper = new ModelCastHelper(
            $this->reflectionProvider,
            $this->parser,
            false,
            self::getContainer()->getByType(ScopeFactory::class),
        );

        $classReflection = $this->reflectionProvider->getClass(MemberWithCustomKey::class);

        // The model uses #[Table(key: 'uuid', keyType: 'string')], so getCasts() returns
        // ['uuid' => 'string']. Without initializeModelAttributes(), primaryKey stays 'id'
        // and the cast for 'uuid' is never registered.
        self::assertSame('string', $modelCastHelper->getCastForProperty($classReflection, 'uuid'));
        self::assertNull($modelCastHelper->getCastForProperty($classReflection, 'id'));
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

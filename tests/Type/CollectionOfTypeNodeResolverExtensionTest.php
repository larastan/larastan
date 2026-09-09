<?php

declare(strict_types=1);

namespace Tests\Type;

use Larastan\Larastan\Types\CollectionOf\CollectionOfTypeNodeResolverExtension;
use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolver;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\Testing\PHPStanTestCase;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\LateResolvableType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\VerbosityLevel;
use PHPUnit\Framework\Attributes\DataProvider;

class CollectionOfTypeNodeResolverExtensionTest extends PHPStanTestCase
{
    private CollectionOfTypeNodeResolverExtension $extension;
    private NameScope $nameScope;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = static::getContainer()->getByType(CollectionOfTypeNodeResolverExtension::class);

        $this->extension->setTypeNodeResolver(static::getContainer()->getByType(TypeNodeResolver::class));

        $this->nameScope = new NameScope(null, []);
    }

    public function testNonGenericTypeNodeReturnsNull(): void
    {
        $typeNode = new IdentifierTypeNode('string');

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testNonCollectionOfGenericTypeReturnsNull(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('model-property'),
            [new IdentifierTypeNode('User')],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testCollectionOfWithNoGenericTypesReturnsErrorType(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testCollectionOfWithMultipleGenericTypesReturnsErrorType(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [
                new IdentifierTypeNode('User'),
                new IdentifierTypeNode('Account'),
            ],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    public function testCollectionOfWithNonModelTypeReturnsErrorType(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [new IdentifierTypeNode('string')],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNull($result);
    }

    #[DataProvider('validModelTypesProvider')]
    public function testCollectionOfWithValidModelTypes(string $modelClass, string $expectedDescription): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [new IdentifierTypeNode($modelClass)],
        );

        $result = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNotNull($result);
        $this->assertInstanceOf(LateResolvableType::class, $result);
        $this->assertStringContainsString($expectedDescription, $result->resolve()->describe(VerbosityLevel::value()));
    }

    public function testCollectionKeysAreCompatibleWithIntegerAndStringKeys(): void
    {
        $typeNode = new GenericTypeNode(
            new IdentifierTypeNode('collection-of'),
            [new IdentifierTypeNode('App\Account')],
        );

        $collection = $this->extension->resolve($typeNode, $this->nameScope);

        $this->assertNotNull($collection);
        $this->assertInstanceOf(BenevolentUnionType::class, $collection->getIterableKeyType());

        foreach ([new IntegerType(), new StringType()] as $keyType) {
            $typedCollection = new GenericObjectType('App\AccountCollection', [$keyType, new ObjectType('App\Account')]);

            $this->assertTrue($typedCollection->accepts($collection, true)->yes());
            $this->assertTrue($collection->accepts($typedCollection, true)->yes());
        }
    }

    /** @return array<string, array{string, string}> */
    public static function validModelTypesProvider(): array
    {
        return [
            'User model with standard collection' => [
                'App\User',
                'Collection<(int|string), App\User>',
            ],
            'Post model with standard collection' => [
                'App\Post',
                'Collection<(int|string), App\Post>',
            ],
            'Transaction model with custom collection' => [
                'App\Transaction',
                'App\TransactionCollection',
            ],
            'Account model with custom collection' => [
                'App\Account',
                'App\AccountCollection',
            ],
        ];
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../../extension.neon',
        ];
    }
}

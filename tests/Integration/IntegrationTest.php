<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\Error;
use PHPStan\File\FileHelper;
use PHPStan\Testing\PHPStanTestCase;
use Throwable;

use function count;
use function implode;
use function sprintf;

class IntegrationTest extends PHPStanTestCase
{
    /** @return iterable<mixed> */
    public static function dataIntegrationTests(): iterable
    {
        self::getContainer();

        yield [__DIR__ . '/data/bug-2074.php'];
        yield [__DIR__ . '/data/test-case-extension.php', [34 => ['Call to function method_exists() with $this(TestTestCase) and \'partialMock\' will always evaluate to true.']]];
        yield [__DIR__ . '/data/model-builder.php'];
        yield [__DIR__ . '/data/model-properties.php'];
        yield [__DIR__ . '/data/blade-view.php'];
        yield [__DIR__ . '/data/helpers.php'];

        yield [
            __DIR__ . '/data/model-property-builder.php',
            [
                15 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::firstWhere() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                16 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::firstWhere() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'id\'|\'unionNotExisting\' given.'],
                17 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::where() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                19 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::where() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                20 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::where() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                24 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::where() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, string given.'],
                25 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::orWhere() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                26 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::orWhere() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'foo\' given.'],
                27 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::orWhere() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, array{foo: \'foo\'} given.'],
                30 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::value() expects Illuminate\Contracts\Database\Query\Expression|model property of App\User, string given.'],
                35 => ['Parameter #1 $columns of method Illuminate\Database\Eloquent\Builder<App\User>::first() expects array<int, model property of App\User>|model property of App\User, array<int, string> given.'],
                36 => ['Parameter #1 $columns of method Illuminate\Database\Eloquent\Builder<App\User>::first() expects array<int, model property of App\User>|model property of App\User, string given.'],
                39 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\User>::where() expects array<int|model property of App\User, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): Illuminate\Database\Eloquent\Builder<App\User>)|(Closure(Illuminate\Database\Eloquent\Builder<App\User>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\User, \'roles.foo\' given.'],
            ],
        ];

        yield [
            __DIR__ . '/data/model-property-model.php',
            [
                11 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Model::update() expects array<model property of static(ModelPropertyModel\ModelPropertyOnModel), mixed>, array<string, string> given.'],
                18 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Model::update() expects array<model property of App\Account|App\User, mixed>, array<string, string> given.'],
                25 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Model::update() expects array<model property of App\Account|App\User, mixed>, array<string, string> given.'],
                49 => ['Parameter #1 $property of method ModelPropertyModel\ModelPropertyCustomMethods::foo() expects model property of App\User, string given.'],
                68 => ['Parameter #1 $property of method ModelPropertyModel\ModelPropertyCustomMethodsInNormalClass::foo() expects model property of App\User, string given.'],
                94 => ['Parameter #1 $userModelProperty of function ModelPropertyModel\acceptsUserProperty expects model property of App\User, model property of App\Account given.'],
                107 => ['Parameter #1 $accountModelProperty of function ModelPropertyModel\acceptsUserOrAccountProperty expects model property of App\Account|App\User, string given.'],
            ],
        ];

        yield [
            __DIR__ . '/data/model-property-model-factory.php',
            [
                7 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Factories\Factory<App\User>::createOne() expects array<model property of App\User, mixed>, array<string, string> given.'],
            ],
        ];

        yield [
            __DIR__ . '/data/model-property-relation.php',
            [
                4 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\Account>::where() expects array<int|model property of App\Account, mixed>|(Closure(Illuminate\Database\Eloquent\Builder<App\Account>): Illuminate\Database\Eloquent\Builder<App\Account>)|(Closure(Illuminate\Database\Eloquent\Builder<App\Account>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\Account, \'foo\' given.'],
                5 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Relations\HasOneOrMany<App\Account,App\User,Illuminate\Database\Eloquent\Collection<int, App\Account>>::create() expects array<model property of App\Account, mixed>, array<string, string> given.'],
                6 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Relations\HasOneOrMany<App\Account,App\User,Illuminate\Database\Eloquent\Collection<int, App\Account>>::firstOrNew() expects array<model property of App\Account, mixed>, array<string, string> given.'],
                7 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Relations\HasOneOrMany<App\Account,App\User,Illuminate\Database\Eloquent\Collection<int, App\Account>>::firstOrCreate() expects array<model property of App\Account, mixed>, array<string, string> given.'],
                8 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Relations\HasOneOrMany<App\Account,App\User,Illuminate\Database\Eloquent\Collection<int, App\Account>>::updateOrCreate() expects array<model property of App\Account, mixed>, array<string, string> given.'],
                10 => ['Parameter #1 $column of method Illuminate\Database\Eloquent\Builder<App\Post>::where() expects array<int|model property of App\Post, mixed>|(Closure(App\PostBuilder<App\Post>): App\PostBuilder<App\Post>)|(Closure(App\PostBuilder<App\Post>): void)|Illuminate\Contracts\Database\Query\Expression|model property of App\Post, \'foo\' given.'],
                12 => ['Parameter #1 $attributes of method Illuminate\Database\Eloquent\Relations\HasOneOrMany<App\Account,App\User,Illuminate\Database\Eloquent\Collection<int, App\Account>>::createOrFirst() expects array<model property of App\Account, mixed>, array<string, string> given.'],
            ],
        ];

        yield [
            __DIR__ . '/data/model-property-static-call.php',
            [
                10 => ['Parameter #1 $attributes of static method Illuminate\Database\Eloquent\Builder<App\User>::create() expects array<model property of App\User, mixed>, array<string, string> given.'],
                14 => ['Parameter #1 $attributes of static method Illuminate\Database\Eloquent\Builder<App\User>::create() expects array<model property of App\User, mixed>, array<string, string> given.'],
                26 => ['Parameter #1 $attributes of static method Illuminate\Database\Eloquent\Builder<ModelPropertyStaticCall\ModelPropertyStaticCallsInClass>::create() expects array<model property of ModelPropertyStaticCall\ModelPropertyStaticCallsInClass, mixed>, array<string, string> given.'],
                34 => ['Parameter #1 $attributes of static method Illuminate\Database\Eloquent\Builder<ModelPropertyStaticCall\ModelPropertyStaticCallsInClass>::create() expects array<model property of ModelPropertyStaticCall\ModelPropertyStaticCallsInClass, mixed>, array<string, string> given.'],
            ],
        ];

        yield [
            __DIR__ . '/data/model-property-mutator-and-casting.php',
            [
                24 => ['Parameter #1 $lineOne of class ModelPropertyMutatorAndCasting\Address constructor expects string, mixed given.'],
                25 => ['Parameter #2 $lineTwo of class ModelPropertyMutatorAndCasting\Address constructor expects string, mixed given.'],
            ],
        ];
    }

    /**
     * @param array<int, array<int, string>> $expectedErrors
     *
     * @dataProvider dataIntegrationTests
     */
    public function testIntegration(string $file, array|null $expectedErrors = null): void
    {
        $errors = $this->runAnalyse($file);

        if ($expectedErrors === null) {
            $this->assertNoErrors($errors);
        } else {
            if (count($expectedErrors) > 0) {
                $this->assertNotEmpty($errors);
            }

            $this->assertSameErrorMessages($file, $expectedErrors, $errors);
        }
    }

    /**
     * @see https://github.com/phpstan/phpstan-src/blob/c9772621c0bd6eab7e02fdaa03714bea239b372d/tests/PHPStan/Analyser/AnalyserIntegrationTest.php#L604-L622
     * @see https://github.com/phpstan/phpstan/discussions/6888#discussioncomment-2423613
     *
     * @param string[]|null $allAnalysedFiles
     *
     * @return Error[]
     *
     * @throws Throwable
     */
    private function runAnalyse(string $file, array|null $allAnalysedFiles = null): array
    {
        $file = $this->getFileHelper()->normalizePath($file);

        /** @var Analyser $analyser */
        $analyser = self::getContainer()->getByType(Analyser::class); // @phpstan-ignore-line

        /** @var FileHelper $fileHelper */
        $fileHelper = self::getContainer()->getByType(FileHelper::class);

        $errors = $analyser->analyse([$file], null, null, true, $allAnalysedFiles)->getErrors(); // @phpstan-ignore-line

        foreach ($errors as $error) {
            $this->assertSame($fileHelper->normalizePath($file), $error->getFilePath());
        }

        return $errors;
    }

    /**
     * @param array<int, array<int, string>> $expectedErrors
     * @param Error[]                        $errors
     */
    private function assertSameErrorMessages(string $file, array $expectedErrors, array $errors): void
    {
        foreach ($errors as $error) {
            $errorLine = $error->getLine() ?? 0;

            $this->assertArrayHasKey(
                $errorLine,
                $expectedErrors,
                sprintf('File %s has unexpected error "%s" at line %d.', $file, $error->getMessage(), $errorLine),
            );
            $this->assertContains(
                $error->getMessage(),
                $expectedErrors[$errorLine],
                sprintf("File %s has unexpected error \"%s\" at line %d.\n\nExpected \"%s\"", $file, $error->getMessage(), $errorLine, implode("\n\t", $expectedErrors[$errorLine])),
            );
        }
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/../Type/data/config-check-model-properties.neon',
        ];
    }
}

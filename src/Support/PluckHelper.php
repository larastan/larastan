<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Support\Collection;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\CallableType;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;

use function array_filter;
use function array_map;
use function collect;
use function explode;

/** @internal */
final class PluckHelper
{
    public function getArrayType(
        Type $from,
        Arg $valueArg,
        Arg|null $keyArg,
        Scope $scope,
    ): ArrayType {
        $valueType = $this->getTypeFromArg($from, $valueArg, $scope);
        $keyType   = $keyArg === null ? new IntegerType() : $this->getTypeFromArg($from, $keyArg, $scope);

        $keyType   ??= new BenevolentUnionType([new IntegerType(), new StringType()]);
        $valueType ??= new MixedType();

        return new ArrayType($keyType, $valueType);
    }

    public function getCollectionType(
        Type $from,
        Arg $valueArg,
        Arg|null $keyArg,
        Scope $scope,
        string|null $collectionClass = null,
    ): GenericObjectType {
        $type = $this->getArrayType($from, $valueArg, $keyArg, $scope);

        return new GenericObjectType(
            $collectionClass ?? Collection::class,
            [$type->getKeyType(), $type->getItemType()],
        );
    }

    private function getTypeFromArg(Type $from, Arg $arg, Scope $scope): Type|null
    {
        $type = $scope->getType($arg->value);

        if ($type->isCallable()->yes()) {
            return $this->getTypeFromCallable($arg->value, $from, $scope);
        }

        $values = $this->getKeysFromType($type);

        if ($values === []) {
            return null;
        }

        $types = array_filter(array_map(
            fn ($key) => $this->pluckFromType($from, $key, $scope),
            $values,
        ));

        return TypeCombinator::union(...$types);
    }

    private function getTypeFromCallable(Expr $callable, Type $parameterType, Scope $scope): Type|null
    {
        /** @phpstan-ignore phpstanApi.class */
        if (! $scope instanceof MutatingScope) {
            return null;
        }

        /** @phpstan-ignore phpstanApi.method, phpstanApi.constructor */
        $scopeWithContext = $scope->pushInFunctionCall(null, new DummyParameter(
            'callback',
            new CallableType([
                /** @phpstan-ignore phpstanApi.constructor */
                new NativeParameterReflection(
                    'param',
                    false,
                    $parameterType,
                    PassedByReference::createNo(),
                    false,
                    null,
                ),
            ], new MixedType()),
            false,
            PassedByReference::createNo(),
            false,
            null,
        ));

        $callableType = $scopeWithContext->getType($callable);

        if ($callableType instanceof ClosureType) {
            return $callableType->getReturnType();
        }

        return null;
    }

    /** @return array<int, array<int, string>> */
    private function getKeysFromType(Type $type): array
    {
        if ($type->isConstantArray()->yes()) {
            return collect($type->getConstantArrays())
                ->map(
                    static fn ($a) => collect($a->getValueTypes())
                        ->map(static fn ($t) => $t->getConstantStrings()[0] ?? null)
                        ->filter()
                        ->map(static fn ($s) => $s->getValue())
                        ->all() ?: null,
                )
                ->filter()
                ->all();
        }

        return collect($type->getConstantStrings())
            ->map(static fn ($s) => explode('.', $s->getValue()))
            ->all();
    }

    /** @param array<int, string> $keys */
    private function pluckFromType(Type $from, array $keys, Scope $scope): Type|null
    {
        if ($keys === []) {
            return null;
        }

        foreach ($keys as $key) {
            if (! $from->hasProperty($key)->no()) {
                try {
                    $from = $from->getProperty($key, $scope)->getReadableType();

                    continue;
                } catch (Throwable) {
                }
            }

            $keyType = new ConstantStringType($key);

            if (! $from->hasOffsetValueType($keyType)->no()) {
                try {
                    $from = $from->getOffsetValueType($keyType);

                    continue;
                } catch (Throwable) {
                }
            }

            return null;
        }

        return $from;
    }
}

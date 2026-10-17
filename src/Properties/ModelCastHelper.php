<?php

declare(strict_types=1);

namespace Larastan\Larastan\Properties;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\CastsInboundAttributes;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\Casts\AsEncryptedArrayObject;
use Illuminate\Database\Eloquent\Casts\AsEncryptedCollection;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUniqueStringIds;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon as IlluminateCarbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Stringable as IlluminateStringable;
use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MissingMethodFromReflectionException;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionException;
use stdClass;
use Stringable;

use function array_key_exists;
use function array_keys;
use function class_exists;
use function count;
use function explode;
use function in_array;
use function is_string;
use function str_replace;

class ModelCastHelper
{
    /** @var array<string, array<string, string|Type>> */
    private array $modelCasts = [];

    /** @var array<string, Type> */
    private array $parsedCastsMethods = [];

    /** @var array<string, true> */
    private array $castsWithUnknownKeys = [];

    public function __construct(
        private ReflectionProvider $reflectionProvider,
        private Parser $parser,
        private bool $parseModelCastsMethod,
        private ScopeFactory $scopeFactory,
    ) {
    }

    public function getReadableType(string|Type $cast, Type $originalType): Type
    {
        if (! is_string($cast)) {
            return $cast;
        }

        $cast = $this->parseCast($cast);

        $attributeType = match ($cast) {
            'int', 'integer', 'timestamp' => $originalType->isInteger()->yes() ? $originalType : new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()),
            'string' => new StringType(),
            'bool', 'boolean' => new BooleanType(),
            'object' => new ObjectType(stdClass::class),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType(Collection::class),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType(CarbonImmutable::class),
            AsArrayObject::class, AsEncryptedArrayObject::class => new ObjectType(ArrayObject::class),
            AsCollection::class, AsEncryptedCollection::class => new BenevolentUnionType([
                new GenericObjectType(Collection::class, [new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()]),
                new NullType(),
            ]),
            AsStringable::class => new ObjectType(IlluminateStringable::class),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isEnum()) {
            return new ObjectType($cast);
        }

        if ($classReflection->is(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn  = $methodReflection->getVariants()[0]->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if ($classReflection->is(CastsAttributes::class)) {
            $methodReflection = $classReflection->getNativeMethod('get');

            return $methodReflection->getVariants()[0]->getReturnType();
        }

        if ($classReflection->is(CastsInboundAttributes::class)) {
            return $originalType;
        }

        return new MixedType();
    }

    public function getWriteableType(string|Type $cast, Type $originalType): Type
    {
        if (! is_string($cast)) {
            return $cast;
        }

        $cast = $this->parseCast($cast);

        $attributeType = match ($cast) {
            'int', 'integer', 'timestamp' => $originalType->isInteger()->yes() ? $originalType : new IntegerType(),
            'real', 'float', 'double' => new FloatType(),
            'decimal' => TypeCombinator::union(TypeCombinator::intersect(new StringType(), new AccessoryNumericStringType()), new IntegerType(), new FloatType()),
            'string' => new StringType(),
            'bool', 'boolean' => TypeCombinator::union(new BooleanType(), new ConstantIntegerType(0), new ConstantIntegerType(1)),
            'object' => new ObjectType(stdClass::class),
            'array', 'json' => new ArrayType(new BenevolentUnionType([new IntegerType(), new StringType()]), new MixedType()),
            'collection' => new ObjectType(Collection::class),
            'date', 'datetime' => $this->getDateType(),
            'immutable_date', 'immutable_datetime' => new ObjectType(CarbonImmutable::class),
            AsArrayObject::class, AsCollection::class,
            AsEncryptedArrayObject::class, AsEncryptedCollection::class => new MixedType(),
            AsStringable::class => TypeCombinator::union(new StringType(), new ObjectType(Stringable::class)),
            default => null,
        };

        if ($attributeType) {
            return $attributeType;
        }

        if (! $this->reflectionProvider->hasClass($cast)) {
            return $originalType;
        }

        $classReflection = $this->reflectionProvider->getClass($cast);

        if ($classReflection->isEnum()) {
            return new ObjectType($cast);
        }

        if ($classReflection->is(Castable::class)) {
            $methodReflection = $classReflection->getNativeMethod('castUsing');
            $castUsingReturn  = $methodReflection->getVariants()[0]->getReturnType();

            if ($castUsingReturn->getObjectClassReflections() !== []) {
                $classReflection = $castUsingReturn->getObjectClassReflections()[0];
            }
        }

        if (
            $classReflection->is(CastsAttributes::class)
            || $classReflection->is(CastsInboundAttributes::class)
        ) {
            $methodReflection = $classReflection->getNativeMethod('set');
            $parameters       = $methodReflection->getVariants()[0]->getParameters();

            $valueParameter = Arr::first($parameters, static fn (ParameterReflection $parameterReflection) => $parameterReflection->getName() === 'value');

            if ($valueParameter) {
                return $valueParameter->getType();
            }
        }

        return new MixedType();
    }

    public function getDateType(): Type
    {
        $dateClass = class_exists(Date::class)
            ? Date::now()::class
            : IlluminateCarbon::class;

        if ($dateClass === IlluminateCarbon::class) {
            return new ObjectType(Carbon::class);
        }

        return new ObjectType($dateClass);
    }

    private function parseCast(string $cast): string
    {
        foreach (explode(':', $cast) as $part) {
            // If the cast is prefixed with `encrypted:` we need to skip to the next
            if ($part === 'encrypted') {
                continue;
            }

            return $part;
        }

        return $cast;
    }

    public function hasCastForProperty(ClassReflection $modelClassReflection, string $propertyName): bool
    {
        $modelCasts = $this->getModelCasts($modelClassReflection);

        return isset($this->castsWithUnknownKeys[$modelClassReflection->getName()])
            || array_key_exists($propertyName, $modelCasts);
    }

    public function getCastForProperty(ClassReflection $modelClassReflection, string $propertyName): string|Type|null
    {
        $modelCasts = $this->getModelCasts($modelClassReflection);

        // An unknown casts() key may override any schema-derived property
        // or a previous entry from $casts. Do not claim an exact type.
        if (isset($this->castsWithUnknownKeys[$modelClassReflection->getName()])) {
            return new MixedType();
        }

        return $modelCasts[$propertyName] ?? null;
    }

    /**
     * @return array<string, string|Type>
     *
     * @throws ShouldNotHappenException
     * @throws MissingMethodFromReflectionException
     */
    private function getModelCasts(ClassReflection $modelClassReflection): array
    {
        $className = $modelClassReflection->getName();

        if (array_key_exists($className, $this->modelCasts)) {
            return $this->modelCasts[$className];
        }

        try {
            $modelInstance = ModelHelper::newInstanceWithoutConstructor($modelClassReflection);
        } catch (ReflectionException) {
            throw new ShouldNotHappenException();
        }

        if (
            $modelClassReflection->hasTraitUse(HasUniqueStringIds::class)
            || $modelClassReflection->hasTraitUse(HasUuids::class)
            || $modelClassReflection->hasTraitUse(HasUlids::class)
        ) {
            $modelInstance->usesUniqueIds = true;
        }

        $modelCasts = $modelInstance->getCasts();

        $castsMethodReturnType = $modelClassReflection->getMethod(
            'casts',
            new OutOfClassScope(),
        )->getVariants()[0]->getReturnType();

        // Constant-array PHPDoc already provides the precise casts. Parse only
        // when native `casts(): array` gives PHPStan insufficient information.
        // Models inheriting the framework's empty casts() have nothing to parse.
        if (
            $this->parseModelCastsMethod
            && ! $castsMethodReturnType->isConstantArray()->yes()
            && $modelClassReflection->getNativeMethod('casts')->getDeclaringClass()->getName() !== Model::class
        ) {
            $parsed = $this->parseCastsMethod($modelClassReflection);

            if ($parsed->isConstantArray()->yes()) {
                $castsMethodReturnType = $parsed;
            } else {
                // When parsing cannot determine a literal array shape, casts()
                // can supply ANY key at runtime. Keeping a column's schema
                // type or previous $casts entry would be unsound.
                $this->castsWithUnknownKeys[$className] = true;
            }
        }

        $modelCasts = $this->mergeCastsFromType($modelCasts, $castsMethodReturnType);

        $this->modelCasts[$className] = $modelCasts;

        return $modelCasts;
    }

    /**
     * Preserve exact casts and widen unresolved values for a known key.
     * This applies equally to parsed bodies and declared PHPDoc array shapes.
     *
     * @param array<string, string|Type> $modelCasts
     *
     * @return array<string, string|Type>
     */
    private function mergeCastsFromType(array $modelCasts, Type $type): array
    {
        if (! $type->isConstantArray()->yes()) {
            return $modelCasts;
        }

        $arrays = $type->getConstantArrays();

        if ($arrays === []) {
            return $modelCasts;
        }

        foreach ($arrays as $array) {
            $optional = $array->getOptionalKeys();
            $values   = $array->getValueTypes();

            foreach ($array->getKeyTypes() as $index => $key) {
                if (! $this->isLiteralString($key)) {
                    // An unknown key can shadow any existing $casts entry.
                    foreach (array_keys($modelCasts) as $name) {
                        $modelCasts[$name] = new MixedType();
                    }

                    continue;
                }

                $name  = $key->getConstantStrings()[0]->getValue();
                $value = $values[$index];

                // Optional keys and alternative constant arrays can override
                // existing $casts only on some paths; never select one branch.
                $modelCasts[$name] = count($arrays) === 1
                    && ! in_array($index, $optional, true)
                    && $this->isLiteralString($value)
                    ? str_replace('\\\\', '\\', $value->getConstantStrings()[0]->getValue())
                    : new MixedType();
            }
        }

        return $modelCasts;
    }

    private function isLiteralString(Type $type): bool
    {
        // Use PHPStan's native type API, not instanceof checks on Type.
        return $type->isString()->yes()
            && $type->isConstantScalarValue()->yes()
            && count($type->getConstantStrings()) === 1;
    }

    private function parseCastsMethod(ClassReflection $modelClassReflection): Type
    {
        $castsMethod    = $modelClassReflection->getNativeMethod('casts');
        $declaringClass = $castsMethod->getDeclaringClass();
        $className      = $declaringClass->getName();

        if (isset($this->parsedCastsMethods[$className])) {
            return $this->parsedCastsMethods[$className];
        }

        // The declaring model may import casts() from a trait. In that case
        // the method body is in the trait's file, not the model's file.
        // Native method reflection identifies the actual source location.
        $nativeMethod = $declaringClass->getNativeReflection()->getMethod($castsMethod->getName());
        $fileName     = $nativeMethod->getFileName();
        $startLine    = $nativeMethod->getStartLine();
        $endLine      = $nativeMethod->getEndLine();

        if ($fileName === false || $startLine === false || $endLine === false) {
            return $this->parsedCastsMethods[$className] = new NullType();
        }

        $stmts = $this->parser->parseFile($fileName);

        // A trait method can be imported as "casts" under an alias even
        // when its source declaration uses a different method name. Match
        // the exact reflected method span and refuse ambiguous matches.
        $method = null;

        foreach ((new NodeFinder())->findInstanceOf($stmts, Node\Stmt\ClassMethod::class) as $candidate) {
            if ($candidate->getStartLine() !== $startLine || $candidate->getEndLine() !== $endLine) {
                continue;
            }

            if ($method !== null) {
                return $this->parsedCastsMethods[$className] = new NullType();
            }

            $method = $candidate;
        }

        if ($method === null) {
            return $this->parsedCastsMethods[$className] = new NullType();
        }

        // Earlier or nested returns can change the casts shape.
        $returns = (new NodeFinder())->findInstanceOf($method->stmts ?? [], Node\Stmt\Return_::class);

        if (count($returns) !== 1) {
            return $this->parsedCastsMethods[$className] = new NullType();
        }

        // Only a direct literal array return is inferred; delegated
        // expressions retain their declared return type.
        foreach ($method->stmts ?? [] as $bodyStatement) {
            if ($bodyStatement instanceof Node\Stmt\Return_ && $bodyStatement->expr instanceof Array_) {
                // self:: must resolve in the class using the trait, not in
                // an unrelated file-level or trait-only scope.
                $scope = $this->scopeFactory->create(ScopeContext::create($fileName))->enterClass($declaringClass);

                return $this->parsedCastsMethods[$className] = $scope->getType($bodyStatement->expr);
            }
        }

        return $this->parsedCastsMethods[$className] = new NullType();
    }
}

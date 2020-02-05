<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\ReturnTypes;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Methods\ModelTypeHelper;
use NunoMaduro\Larastan\Methods\Pipes\Mixins;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use ReflectionClass;

/**
 * @internal
 */
final class ModelExtension implements DynamicStaticMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * @var \NunoMaduro\Larastan\Methods\Pipes\Mixins
     */
    private $mixins;

    /**
     * @param \NunoMaduro\Larastan\Methods\Pipes\Mixins $mixins
     *
     * @return void
     */
    public function __construct(Mixins $mixins = null)
    {
        $this->mixins = $mixins ?? new Mixins();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Model::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        $name = $methodReflection->getName();
        if ($name === '__construct') {
            return false;
        }

        return $methodReflection->getDeclaringClass()->hasNativeMethod($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromStaticMethodCall(
        MethodReflection $methodReflection,
        StaticCall $methodCall,
        Scope $scope
    ): Type {
        $method = $methodReflection->getDeclaringClass()
            ->getMethod($methodReflection->getName(), $scope);

        $variants = $method->getVariants();
        $returnType = $variants[0]->getReturnType();

        /*
         * If the method returns a static type, we instruct phpstan that
         * "static" points to the concrete class model.
         */
        if ($methodCall->class instanceof \PhpParser\Node\Name && $variants[0] instanceof FunctionVariantWithPhpDocs) {
            $className = $methodCall->class->toString();
            if (class_exists($className)) {
                $classReflection = new ReflectionClass($className);
                $isValidInstance = false;
                foreach ($this->mixins->getMixinsFromClass(
                    $this->broker,
                    $this->broker->getClass(Collection::class)
                ) as $mixin) {
                    if ($isValidInstance = $classReflection->isSubclassOf($mixin)) {
                        break;
                    }
                }

                if ($isValidInstance) {
                    $returnType = ModelTypeHelper::replaceStaticTypeWithModel($returnType, $className);
                }
            }
        }

        if ((count(array_intersect([EloquentBuilder::class, QueryBuilder::class], $returnType->getReferencedClasses())) > 0)
            && $methodCall->class instanceof \PhpParser\Node\Name
        ) {
            $returnType = new GenericObjectType(EloquentBuilder::class, [new ObjectType($scope->resolveName($methodCall->class))]);
        }

        if ($methodReflection->getName() === 'all' && in_array(Collection::class, $returnType->getReferencedClasses(), true) && $methodCall->class instanceof \PhpParser\Node\Name) {
            $returnType = new GenericObjectType(Collection::class, [new ObjectType($scope->resolveName($methodCall->class))]);
        }

        return $returnType;
    }
}

<?php

declare(strict_types=1);

/**
 * This file is part of Larastan.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\Larastan;

use function count;
use ReflectionClass;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\IterableType;
use PHPStan\Type\IntersectionType;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\MethodReflection;
use NunoMaduro\Larastan\Middlewares\Mixins;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

/**
 * @internal
 */
final class ModelStaticReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * @var \NunoMaduro\Larastan\Middlewares\Mixins
     */
    private $mixins;

    /**
     * @param \NunoMaduro\Larastan\Middlewares\Mixins $mixins
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
        return $methodReflection->getDeclaringClass()
            ->hasNativeMethod($methodReflection->getName());
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

        $returnType = $method->getVariants()[0]->getReturnType();
        $variants = $method->getVariants();

        /*
         * If the method returns a static type, we instruct phpstan that
         * "static" points to the concrete class model.
         */
        if ($variants[0] instanceof FunctionVariantWithPhpDocs) {
            $className = $methodCall->class->toString();

            if (class_exists($className)) {

                $classReflection = new ReflectionClass($className);
                $isValidInstance = false;
                foreach ($this->mixins->getMixinsFromClass($this->broker, $this->broker->getClass(Collection::class)) as $mixin) {
                    if ($isValidInstance = $classReflection->isSubclassOf($mixin)) {
                        break;
                    }
                }

                if ($isValidInstance) {
                    $types = method_exists($returnType, 'getTypes') ? $returnType->getTypes() : [$returnType];
                    $types = $this->replaceStaticType($types, $methodCall->class->toString());
                    $returnType = count($types) > 1 ? new UnionType($types) : current($types);
                }
            }
        }

        return $returnType;
    }

    /**
     * Replaces Static Types by the provided Static Type.
     *
     * @param  array $types
     * @param  string $staticType
     * @return array
     */
    private function replaceStaticType(array $types, string $staticType): array
    {
        foreach ($types as $key => $type) {
            if ($type instanceof ObjectType && $type->getClassName() === Model::class) {
                unset($types[$key]);
            }

            if ($type instanceof StaticType) {
                $types[$key] = $type->changeBaseClass($staticType);
            }

            if ($type instanceof IterableType) {
                $types[$key] = $type->changeBaseClass($staticType);
            }

            if ($type instanceof IntersectionType) {
                $types[$key] = new IntersectionType($this->replaceStaticType($type->getTypes(), $staticType));
            }
        }

        return $types;
    }
}

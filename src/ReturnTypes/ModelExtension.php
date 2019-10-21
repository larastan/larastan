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

namespace NunoMaduro\Larastan\ReturnTypes;

use ReflectionClass;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use NunoMaduro\Larastan\Concerns;
use PhpParser\Node\Expr\StaticCall;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use NunoMaduro\Larastan\Methods\Pipes\Mixins;
use NunoMaduro\Larastan\Methods\ModelTypeHelper;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;

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

        return $returnType;
    }
}

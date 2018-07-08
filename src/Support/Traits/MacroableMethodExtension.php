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

namespace NunoMaduro\Larastan\Support\Traits;

use function get_class;
use PHPStan\Reflection\ClassReflection;
use Illuminate\Support\Traits\Macroable;
use PHPStan\Reflection\MethodReflection;
use NunoMaduro\Larastan\Concerns\HasBroker;
use PHPStan\Reflection\BrokerAwareExtension;
use NunoMaduro\Larastan\Concerns\HasContainer;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * @internal
 */
final class MacroableMethodExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use HasBroker, HasContainer;

    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;

    /**
     * AbstractExtension constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     */
    public function __construct(PhpMethodReflectionFactory $methodReflectionFactory)
    {
        $this->methodReflectionFactory = $methodReflectionFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->isInterface()) {

            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                $className = get_class($concrete);
                $haveTrait = $this->broker->getClass($className)
                    ->hasTraitUse(Macroable::class);
            } else {
                $className = null;
                $haveTrait = false;
            }
        } else {
            /** @var \Illuminate\Support\Traits\Macroable $macroable */
            $className = $classReflection->getName();
            $haveTrait = $classReflection->hasTraitUse(Macroable::class);
        }

        return $haveTrait && $className::hasMacro($methodName);
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if ($classReflection->isInterface()) {
            $concrete = $this->getContainer()
                ->make($classReflection->getName());

            $macroable = get_class($concrete);
        } else {
            /** @var \Illuminate\Support\Traits\Macroable $macroable */
            $macroable = $classReflection->getName();
        }

        $refObject = new \ReflectionClass($macroable);
        $refProperty = $refObject->getProperty('macros');
        $refProperty->setAccessible(true);

        $reflectionFunction = new \ReflectionFunction($refProperty->getValue()[$methodName]);

        return $this->methodReflectionFactory->create(
            $classReflection,
            null,
            new Macro(
                $classReflection->getName(), $methodName, $reflectionFunction
            ),
            $reflectionFunction->getParameters(),
            null,
            null,
            false,
            false,
            false
        );
    }
}

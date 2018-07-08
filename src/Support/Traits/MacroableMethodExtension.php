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
use Illuminate\Container\Container;
use PHPStan\Reflection\ClassReflection;
use Illuminate\Support\Traits\Macroable;
use PHPStan\Reflection\MethodReflection;
use NunoMaduro\Larastan\Concerns\HasBroker;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\Php\PhpMethodReflectionFactory;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container as ContainerContract;

/**
 * @internal
 */
final class MacroableMethodExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use HasBroker;

    /**
     * @var \PHPStan\Reflection\Php\PhpMethodReflectionFactory
     */
    private $methodReflectionFactory;

    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;

    /**
     * MacroMethodExtension constructor.
     *
     * @param \PHPStan\Reflection\Php\PhpMethodReflectionFactory $methodReflectionFactory
     * @param \Illuminate\Contracts\Container\Container|null $container
     */
    public function __construct(
        PhpMethodReflectionFactory $methodReflectionFactory,
        ContainerContract $container = null
    ) {
        $this->methodReflectionFactory = $methodReflectionFactory;
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->isInterface()) {

            $concrete = null;
            $className = null;
            try {
                $concrete = ($this->container ?? Container::getInstance())->make($classReflection->getName());
            } catch (BindingResolutionException $exception) {
                // ..
            }

            if ($concrete !== null) {
                $className = get_class($concrete);
                $haveTrait = $this->broker->getClass($className)
                    ->hasTraitUse(Macroable::class);
            } else {
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
            $concrete = ($this->container ?? Container::getInstance())->make($classReflection->getName());

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

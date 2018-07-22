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

namespace NunoMaduro\Larastan\Properties;

use function get_class;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\PropertyReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use Illuminate\Contracts\Container\Container;
use PHPStan\Reflection\PropertiesClassReflectionExtension;

/**
 * @internal
 */
final class Extension implements PropertiesClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasContainer, Concerns\HasBroker;

    /**
     * {@inheritdoc}
     */
    public function hasProperty(ClassReflection $classReflection, string $propertyName): bool
    {
        $hasProperty = false;

        /*
         * @todo Consider apply this rule only for Illuminate\Contracts.
         */
        if ($classReflection->isInterface()) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {

                /*
                 * @todo Consider refactor
                 */
                switch ($concrete) {
                    case $concrete instanceof Container:
                        $hasProperty = $this->resolve($propertyName) !== null;
                        break;
                    default:
                        $hasProperty = $this->broker->getClass(get_class($concrete))
                            ->hasProperty($propertyName);
                }
            }
        }

        return $hasProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function getProperty(ClassReflection $classReflection, string $propertyName): PropertyReflection
    {
        $concrete = $this->resolve($classReflection->getName());

        switch ($concrete) {
            case $concrete instanceof Container:
                $propertyValue = $this->resolve($propertyName);
                $property = new ContainerProperty(
                    $classReflection, $propertyValue
                );
                break;
            default:
                $property = $this->broker->getClass(get_class($concrete))
                    ->getNativeProperty($propertyName);
        }

        return $property;
    }
}

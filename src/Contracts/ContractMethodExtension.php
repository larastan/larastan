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

namespace NunoMaduro\Larastan\Contracts;

use function get_class;
use Illuminate\Container\Container;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\Larastan\AbstractExtension;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Container\BindingResolutionException;

/**
 * @internal
 */
final class ContractMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function subjects(ClassReflection $classReflection, string $methodName): array
    {
        return $this->getConcrete($classReflection);
    }

    /**
     * {@inheritdoc}
     */
    protected function mixins(ClassReflection $classReflection, string $methodName): array
    {
        return $this->getConcrete($classReflection);
    }

    /**
     * @param \PHPStan\Reflection\ClassReflection $classReflection
     * @return array
     */
    private function getConcrete(ClassReflection $classReflection): array
    {
        if ($classReflection->isInterface()) {
            $concrete = $this->resolve($classReflection->getName());

            if ($concrete !== null) {
                return [get_class($concrete)];
            }
        }

        return [];
    }
}

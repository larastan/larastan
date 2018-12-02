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

use function in_array;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @internal
 */
final class LengthAwarePaginatorExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() === LengthAwarePaginator::class) {
            return in_array($methodName, get_class_methods(Collection::class));
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        return $this->broker->getClass(Collection::class)->getNativeMethod($methodName);
    }
}

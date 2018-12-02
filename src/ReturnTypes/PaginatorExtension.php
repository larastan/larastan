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

use function is_a;
use function in_array;
use NunoMaduro\Larastan\Concerns;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use Illuminate\Database\Eloquent\Collection;
use PHPStan\Reflection\BrokerAwareExtension;
use Illuminate\Contracts\Pagination\Paginator;
use PHPStan\Reflection\MethodsClassReflectionExtension;

/**
 * @internal
 */
final class PaginatorExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * {@inheritdoc}
     */
    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if (is_a($classReflection->getName(), Paginator::class, true)) {
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

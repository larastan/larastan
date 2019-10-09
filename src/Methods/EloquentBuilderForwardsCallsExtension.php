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

namespace NunoMaduro\Larastan\Methods;

use NunoMaduro\Larastan\Concerns;
use Illuminate\Database\Query\Builder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;

final class EloquentBuilderForwardsCallsExtension implements MethodsClassReflectionExtension, BrokerAwareExtension
{
    use Concerns\HasBroker;

    /**
     * The methods that should be returned from query builder.
     *
     * @var array
     */
    protected $passthru = [
        'insert', 'insertOrIgnore', 'insertGetId', 'insertUsing', 'getBindings', 'toSql', 'dump', 'dd',
        'exists', 'doesntExist', 'count', 'min', 'max', 'avg', 'average', 'sum', 'getConnection',
    ];

    /**
     * @return ClassReflection
     * @throws \PHPStan\Broker\ClassNotFoundException
     */
    protected function getBuilderReflection(): ClassReflection
    {
        return $this->broker->getClass(Builder::class);
    }

    public function hasMethod(ClassReflection $classReflection, string $methodName): bool
    {
        if ($classReflection->getName() !== EloquentBuilder::class) {
            return false;
        }

        if ($methodName === 'macro') {
            return true;
        }

        if (in_array($methodName, $this->passthru)) {
            return true;
        }

        return $this->getBuilderReflection()->hasNativeMethod($methodName);
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if ($methodName === 'macro') {
            return new EloquentBuilderMethodReflection($methodName, $classReflection, []);
        }

        if (in_array($methodName, $this->passthru)) {
            return $this->getBroker()->getClass(Builder::class)->getNativeMethod($methodName);
        }

        $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);

        return new EloquentBuilderMethodReflection(
            $methodName, $classReflection,
            ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getParameters()
        );
    }
}

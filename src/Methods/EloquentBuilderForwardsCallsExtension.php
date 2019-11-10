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

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use NunoMaduro\Larastan\Concerns;
use NunoMaduro\Larastan\Reflection\EloquentBuilderMethodReflection;
use PHPStan\Reflection\BrokerAwareExtension;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Dummy\DummyMethodReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\CallableType;
use PHPStan\Type\StringType;

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
    private function getBuilderReflection(): ClassReflection
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

        return true;
    }

    public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
    {
        if ($methodName === 'macro') {
            return new EloquentBuilderMethodReflection($methodName, $classReflection, [
                new NativeParameterReflection('name', false, new StringType(), PassedByReference::createNo(), false),
                new NativeParameterReflection('macro', false, new CallableType(), PassedByReference::createNo(), false),
            ]);
        }

        if (in_array($methodName, $this->passthru)) {
            return $this->getBroker()->getClass(Builder::class)->getNativeMethod($methodName);
        }

        // Could be a model scope
        if (! $this->getBuilderReflection()->hasNativeMethod($methodName)) {
            return new DummyMethodReflection($methodName);
        }

        $methodReflection = $this->getBuilderReflection()->getNativeMethod($methodName);
        $parametersAcceptor = ParametersAcceptorSelector::selectSingle($methodReflection->getVariants());

        return new EloquentBuilderMethodReflection(
            $methodName, $classReflection,
            $parametersAcceptor->getParameters(), null,
            $parametersAcceptor->isVariadic()
        );
    }
}

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

namespace NunoMaduro\Larastan\Auth;

use function get_class;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use Illuminate\Container\Container;
use PhpParser\Node\Expr\MethodCall;
use Illuminate\Contracts\Auth\Guard;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use Illuminate\Contracts\Container\Container as ContainerContract;
use PHPStan\Type\UnionType;

/**
 * @internal
 */
final class AuthReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var \Illuminate\Contracts\Container\Container
     */
    private $container;

    /**
     * AuthenticatableExtension constructor.
     *
     * @param \Illuminate\Contracts\Container\Container|null $container
     */
    public function __construct(ContainerContract $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(): string
    {
        return Guard::class;
    }

    /**
     * {@inheritdoc}
     */
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeFromMethodCall(
        MethodReflection $methodReflection,
        MethodCall $methodCall,
        Scope $scope
    ): Type {
        $config = ($this->container ?? Container::getInstance())->get('config');

        $userModel = $config->get('auth.providers.users.model');

        $types = $methodReflection->getVariants()[0]->getReturnType()->getTypes();

        array_push($types, new ObjectType($userModel));

        return new UnionType($types);
    }
}

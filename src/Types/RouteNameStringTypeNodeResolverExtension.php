<?php

declare(strict_types=1);

namespace NunoMaduro\Larastan\Types;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeNodeResolverExtension;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Type;

use function app;
use function is_string;

/**
 * Ensures a 'route-name-string' type in PHPDoc is recognised to be of type RouteNameStringType.
 */
class RouteNameStringTypeNodeResolverExtension implements TypeNodeResolverExtension
{
    /**
     * @var list<string>
     */
    private array $knownRouteNames = [];

    public function __construct()
    {
        $this->refreshRoutes();
    }

    /**
     * Resolves all route names from the router.
     *
     * This is called automatically upon instantiation, so after the application
     * was booted by Larastan's bootstrap file. Therefore, all routes of the
     * application should have been registered and likely won't change.
     *
     * However, this method can be useful to call in your tests, after you
     * manually registered a new route, that you want to use for verification.
     */
    public function refreshRoutes(): void
    {
        $knownRouteNames = [];
        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            if (is_string($name)) {
                $knownRouteNames[] = $name;
            }
        }

        $this->knownRouteNames = $knownRouteNames;
    }

    public function resolve(TypeNode $typeNode, NameScope $nameScope): ?Type
    {
        if ($typeNode instanceof IdentifierTypeNode && $typeNode->__toString() === 'route-name-string') {
            return new RouteNameStringType($this->knownRouteNames);
        }

        return null;
    }
}

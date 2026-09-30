<?php

declare(strict_types=1);

namespace Larastan\Larastan\Internal;

use Larastan\Larastan\Support\ConfigParser;
use PhpParser\Node\Arg;
use PHPStan\Analyser\Scope;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

use function count;

/** @internal */
final class ConfigHelper
{
    public function __construct(private ConfigParser $configParser)
    {
    }

    public function hasConfigPaths(): bool
    {
        return $this->configParser->getConfigPaths() !== [];
    }

    /**
     * @param  Arg[] $args
     *
     * @return Type[]
     */
    public function getReturnTypes(array $args, Scope $scope): array
    {
        $firstArgType = $scope->getType($args[0]->value);

        $defaultArgType = null;

        if (count($args) > 1) {
            $defaultArgType = $scope->getType($args[1]->value);
        }

        $constantStrings = $firstArgType->getConstantStrings();
        $returnTypes     = [];

        if ($defaultArgType !== null) {
            $returnTypes[] = TypeTraverser::map($defaultArgType, static function (Type $type, callable $traverse) use ($scope): Type {
                if ($type->isConstantScalarValue()->no() && $type->isCallable()->yes()) {
                    return $type->getCallableParametersAcceptors($scope)[0]->getReturnType();
                }

                return $traverse($type);
            })->generalize(GeneralizePrecision::lessSpecific());
        }

        return [...$returnTypes, ...$this->configParser->getTypes($constantStrings, $scope)];
    }
}

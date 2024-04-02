<?php 

namespace Larastan\Larastan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Routing\Router;
use Larastan\Larastan\Concerns;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use Larastan\Larastan\Internal\ConsoleApplicationResolver;
/**
 * @implements Rule<FuncCall>
 */
class RouteNameExistenceRule implements Rule
{


	use Concerns\HasContainer;

    /**
     * @var array<int|string,int>
     */
    static ?array $namedRoutes = null;
    /** @var array<string> */
    protected array $methods = ['route', 'to_route'];

	public function getNodeType(): string
	{
		return FuncCall::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
        $name = $node->name;

        if (! $name instanceof Name) {
            return [];
        }

        $functionName = $scope->resolveName($name);

        if (! in_array($functionName, $this->methods)) {
            return [];
        }

		$args = $node->getArgs();

		if (count($args) === 0) {
			return [];
		}

        $argType = $scope->getType($args[0]->value);
        $constantStrings = $argType->getConstantStrings();

        if (!$constantStrings) {
            return [];
        }

        if (!self::$namedRoutes) {
            $router = $this->resolve('router');
            self::$namedRoutes = array_flip(array_keys($router->getRoutes()->getRoutesByName()));
        }
        $routeName = $constantStrings[0]->getValue();

        if (array_key_exists($routeName, self::$namedRoutes)) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Route name: '{$routeName}' does not exist")
                ->identifier('larastan.routeNameDoesNotExists')
                ->build(),
        ];
	}

}
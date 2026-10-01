<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Events\Dispatchable as EventDispatchable;
use Larastan\Larastan\Rules\CheckDispatchArgumentTypesCompatibleWithClassConstructorRule;
use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Classes\InstantiationRule;
use PHPStan\Rules\Functions\CallCallablesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<Rule<Node>> */
class CheckDispatchArgumentTypesCompatibleWithClassConstructorRuleTest extends RuleTestCase
{
    private string $dispatchableClass;

    protected function getRule(): Rule
    {
        // RuleTestCase registers one rule; also run PHPStan's rules for the virtual calls.
        return new /** @implements Rule<Node> */ class (
            new CheckDispatchArgumentTypesCompatibleWithClassConstructorRule($this->createReflectionProvider(), $this->dispatchableClass),
            self::getContainer()->getByType(InstantiationRule::class),
            self::getContainer()->getByType(CallCallablesRule::class),
        ) implements Rule {
            public function __construct(
                private CheckDispatchArgumentTypesCompatibleWithClassConstructorRule $dispatchRule,
                private InstantiationRule $instantiationRule,
                private CallCallablesRule $callablesRule,
            ) {
            }

            public function getNodeType(): string
            {
                return Node::class;
            }

            /** @inheritDoc */
            public function processNode(Node $node, Scope $scope): array
            {
                return match (true) {
                    $node instanceof StaticCall => $this->dispatchRule->processNode($node, $scope),
                    $node instanceof New_ => $this->instantiationRule->processNode($node, $scope),
                    $node instanceof FuncCall => $this->callablesRule->processNode($node, $scope),
                    default => [],
                };
            }
        };
    }

    public function testJobDispatch(): void
    {
        $this->dispatchableClass = Dispatchable::class;

        $this->analyse([__DIR__ . '/data/job-dispatch.php'], [
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters, 2 required.', 5],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters, 2 required.', 6],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters, 2 required.', 7],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 0 parameters, 2 required.', 8],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter, 2 required.', 10],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 11],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 11],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, true given.', 12],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, false given.', 12],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter, 2 required.', 14],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 15],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 15],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, true given.', 16],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, false given.', 16],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter, 2 required.', 18],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 19],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 19],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, true given.', 20],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, false given.', 20],
            ['Class Tests\Rules\Data\LaravelJob constructor invoked with 1 parameter, 2 required.', 22],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 23],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 23],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, true given.', 24],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, false given.', 24],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 26],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 26],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 27],
            ['Class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 30],
            ['Class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 32],
            ['Class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 34],
            ['Class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 36],
            ['Class Tests\Rules\Data\LaravelJobWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 39],
            ['Parameter $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 41],
            ['Parameter $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 41],
            ['Parameter $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 43],
            ['Parameter $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 43],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 45],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 45],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelJob constructor expects string, int given.', 47],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelJob constructor expects int, string given.', 47],
            ['Parameter #1 $foo of closure expects string, int given.', 52],
        ]);
    }

    public function testEventDispatch(): void
    {
        $this->dispatchableClass = EventDispatchable::class;

        $this->analyse([__DIR__ . '/data/event-dispatch.php'], [
            ['Class Tests\Rules\Data\LaravelEvent constructor invoked with 0 parameters, 2 required.', 5],
            ['Class Tests\Rules\Data\LaravelEvent constructor invoked with 1 parameter, 2 required.', 7],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelEvent constructor expects string, int given.', 8],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelEvent constructor expects int, string given.', 8],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelEvent constructor expects string, true given.', 9],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelEvent constructor expects int, false given.', 9],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelEvent constructor expects string, int given.', 11],
            ['Parameter #2 $bar of class Tests\Rules\Data\LaravelEvent constructor expects int, string given.', 11],
            ['Parameter #1 $foo of class Tests\Rules\Data\LaravelEvent constructor expects string, int given.', 12],
            ['Class Tests\Rules\Data\LaravelEventWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 15],
            ['Class Tests\Rules\Data\LaravelEventWithoutConstructor does not have a constructor and must be instantiated without any parameters.', 18],
        ]);
    }
}

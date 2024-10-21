<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;

/**
 * Catches the use of undefined filesystem in laravel storage abstraction.
 *
 * For example:
 * Storage::disk('this-is-not-defined')
 *
 * @implements Rule<StaticCall>
 */
class NoUndefinedFilesystemsRule implements Rule
{
    public function __construct(protected ReflectionProvider $reflectionProvider)
    {
    }

    public function getNodeType(): string
    {
        return StaticCall::class;
    }

    /** @return array<int, RuleError> */
    public function processNode(Node $node, Scope $scope): array
    {
        $name = $node->name;

        if (! $name instanceof Identifier) {
            return [];
        }

        if ($name->name !== 'disk') {
            return [];
        }

        if (! $this->isCalledOnStorage($node, $scope)) {
            return [];
        }

        // Check if the disk is declared
        $declaredDisks = Config::get('filesystems.disks', []);
        $selectedDisk  = $this->getSelectedDiskFromArgs($node);

        if (isset($declaredDisks[$selectedDisk])) {
            return [];
        }

        return [
            RuleErrorBuilder::message("Called 'Storage::disk()' with an undefined filesystem disk.")
                ->identifier('larastan.noUndefinedFilesystems')
                ->line($node->getLine())
                ->file($scope->getFile())
                ->build(),
        ];
    }

    /**
     * Was the expression called on a Storage instance?
     */
    protected function isCalledOnStorage(StaticCall $call, Scope $scope): bool
    {
        $class = $call->class;
        if ($class instanceof FullyQualified) {
            $type = new ObjectType($class->toString());
        } elseif ($class instanceof Expr) {
            $type = $scope->getType($class);

            if ($type->isClassStringType()->yes() && $type->getConstantStrings() !== []) {
                $type = new ObjectType($type->getConstantStrings()[0]->getValue());
            }
        } else {
            // TODO can we handle relative names, do they even occur here?
            return false;
        }

        return (new ObjectType(Storage::class))
            ->isSuperTypeOf($type)
            ->yes();
    }

    protected function getSelectedDiskFromArgs(StaticCall $node): string
    {
        $args = $node->getRawArgs();

        if (! isset($args[0])) {
            return '';
        }

        if (! isset($args[0]->value)) {
            return '';
        }

        return $args[0]->value->value;
    }
}

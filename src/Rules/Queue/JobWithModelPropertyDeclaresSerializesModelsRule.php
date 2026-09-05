<?php

declare(strict_types=1);

namespace Larastan\Larastan\Rules\Queue;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;
use Larastan\Larastan\Concerns\InspectsQueuedJobs;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionProperty;

use function array_map;
use function count;
use function implode;
use function sprintf;

/**
 * A queued job (one implementing `ShouldQueue`) that holds an Eloquent model in a
 * public property must use the `SerializesModels` trait.
 *
 * A queued job is serialized to the queue store at dispatch and unserialized in
 * the worker. Without `SerializesModels` an Eloquent model property is serialized
 * whole: the full attribute set, loaded relations and casts go onto the wire,
 * bloating the payload, and the job runs against a frozen snapshot taken at
 * dispatch time, so any change made between dispatch and execution is silently
 * lost. `SerializesModels` instead stores just the class name and primary key
 * (plus the loaded relation names) and re-resolves the model fresh from the
 * database when the job runs, keeping the payload small and the data current. A
 * model deleted in the meantime then surfaces as a `ModelNotFoundException`
 * instead of the job operating on stale data.
 *
 * The rule fires only for public properties, because the queue serialization
 * boundary makes public state the concern. Properties typed against a model
 * (including nullable unions) count, and an inherited `SerializesModels` (used by
 * the class, a parent, or another trait) satisfies the rule.
 *
 * @implements Rule<InClassNode>
 */
class JobWithModelPropertyDeclaresSerializesModelsRule implements Rule
{
    use InspectsQueuedJobs;

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();

        if (! $this->isDispatchableClass($classReflection)) {
            return [];
        }

        if (! $classReflection->is(ShouldQueue::class)) {
            return [];
        }

        if ($this->usesTrait($classReflection, SerializesModels::class)) {
            return [];
        }

        $modelProperties = $this->modelTypedPublicProperties($classReflection);

        if ($modelProperties === []) {
            return [];
        }

        return [
            RuleErrorBuilder::message(sprintf(
                "Job '%s' holds Eloquent model%s in public propert%s (%s) but does not use the SerializesModels trait, so each model is serialized whole onto the queue and rehydrated from a stale dispatch time snapshot. Add 'use Illuminate\Queue\SerializesModels;' to the job.",
                $classReflection->getDisplayName(),
                count($modelProperties) === 1 ? '' : 's',
                count($modelProperties) === 1 ? 'y' : 'ies',
                implode(', ', array_map(static fn (string $name): string => '$' . $name, $modelProperties)),
            ))
                ->identifier('larastan.jobSerializesModels')
                ->line($node->getStartLine())
                ->build(),
        ];
    }

    /**
     * Public, non static properties declared on this class whose type references
     * an Eloquent model. Only properties declared here are considered: an
     * inherited property is the declaring class's responsibility, so a missing
     * trait is reported once at its source rather than again on every subclass.
     *
     * @return list<string>
     */
    private function modelTypedPublicProperties(ClassReflection $classReflection): array
    {
        $names = [];

        foreach ($classReflection->getNativeReflection()->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic()) {
                continue;
            }

            if ($property->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            $type = $classReflection->getNativeProperty($property->getName())->getReadableType();

            if (! $this->typeReferencesModel($type)) {
                continue;
            }

            $names[] = $property->getName();
        }

        return $names;
    }

    private function typeReferencesModel(Type $type): bool
    {
        // A union carrying null reports no object class reflections, so strip it
        // first. The result then flattens `Model` and `Foo|Bar` member by member.
        foreach (TypeCombinator::removeNull($type)->getObjectClassReflections() as $classReflection) {
            if ($classReflection->is(Model::class)) {
                return true;
            }
        }

        return false;
    }
}

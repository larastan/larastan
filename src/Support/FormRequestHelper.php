<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support;

use Illuminate\Foundation\Http\FormRequest;
use Larastan\Larastan\Internal\RecursionGuard;
use Larastan\Larastan\Support\Validation\DataView;
use Larastan\Larastan\Support\Validation\RuleParser;
use Larastan\Larastan\Support\Validation\RuleTree;
use Larastan\Larastan\Support\Validation\RuleTypes;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeContext;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Parser\Parser;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeUtils;

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_slice;
use function array_values;
use function in_array;
use function str_starts_with;
use function strtolower;

/** @internal */
final class FormRequestHelper
{
    /** Hooks Laravel calls on a form request before its data is validated. */
    public const BEFORE_VALIDATION = [
        'authorize',
        'prepareForValidation',
        'rules',
        'validationData',
        'messages',
        'attributes',
        'withValidator',
        'after',
        'isPrecognitive',
        'filterPrecognitiveRules',
    ];

    /** What Laravel's own FormRequest methods are for, once validation has passed. */
    private const AFTER_VALIDATION = ['passedvalidation', 'validated', 'safe'];

    /** @var array<string, array{Type, Type}|null> */
    private array $shapes = [];

    public function __construct(
        private Parser $parser,
        private ScopeFactory $scopeFactory,
        private NodeScopeResolver $nodeScopeResolver,
        private RuleParser $ruleParser,
    ) {
    }

    /**
     * The form request classes of a type that is certain to be a form request.
     *
     * @return list<ClassReflection>
     */
    public function requests(Type $type): array
    {
        if (! (new ObjectType(FormRequest::class))->isSuperTypeOf($type)->yes()) {
            return [];
        }

        return array_values(array_filter(
            $type->getObjectClassReflections(),
            static fn (ClassReflection $class): bool => $class->is(FormRequest::class),
        ));
    }

    /** Whether the request is the one being validated, inside a method that runs before validation finished. */
    public function isBeforeValidation(Type $request, Scope $scope): bool
    {
        $method = strtolower((string) $scope->getFunctionName());

        if (TypeUtils::findThisType($request) === null || in_array($method, self::AFTER_VALIDATION, true)) {
            return false;
        }

        // Besides the hooks, everything Laravel's FormRequest declares itself runs before, or instead of, a successful validation.
        return in_array($method, array_map(strtolower(...), [...self::BEFORE_VALIDATION, 'validator']), true)
            || (new ObjectType(FormRequest::class))->getClassReflection()?->hasNativeMethod($method) === true;
    }

    /** Whether the class uses Laravel's own implementation of a method. */
    public function inherits(ClassReflection $class, string $method): bool
    {
        return $class->hasMethod($method)
            && str_starts_with($class->getMethod($method, new OutOfClassScope())->getDeclaringClass()->getName(), 'Illuminate\\');
    }

    /** @return array{Type, Type}|null The array shapes of the validated data and of the input once it passed validation. */
    public function shapes(ClassReflection $request): array|null
    {
        if (! $request->hasNativeMethod('rules')) {
            return null;
        }

        $declaringClass = $request->getNativeMethod('rules')->getDeclaringClass();
        $name           = $declaringClass->getName();

        if (! array_key_exists($name, $this->shapes)) {
            // The rules may read the request they belong to.
            $this->shapes[$name] = RecursionGuard::run(self::class . $name, fn (): array|null => $this->analyse($declaringClass));
        }

        return $this->shapes[$name];
    }

    /** @return array{Type, Type}|null */
    private function analyse(ClassReflection $class): array|null
    {
        $returns = [];

        // Only the method is walked, as part of the class even when a trait declares it: PHPStan's own walk
        // skips traits outside the analysed paths, and inside a trait it forgets the value of __NAMESPACE__.
        foreach ([$class, ...array_values($class->getTraits(true))] as $owner) {
            $file        = $owner->getFileName();
            $declaration = $file === null ? null : (new NodeFinder())->findFirst(
                $this->parser->parseFile($file),
                // Anonymous classes have no namespaced name.
                static fn (Node $node): bool => $node instanceof ClassLike && isset($node->namespacedName) && $node->namespacedName->toString() === $owner->getName(),
            );
            $method = $declaration instanceof ClassLike ? $declaration->getMethod('rules') : null;

            if ($file === null || $method === null) {
                continue;
            }

            $scope     = $this->scopeFactory->create(ScopeContext::create($file));
            $namespace = $owner->getNativeReflection()->getNamespaceName();

            $this->nodeScopeResolver->processNodes(
                [$method],
                ($namespace === '' ? $scope : $scope->enterNamespace($namespace))->enterClass($class),
                function (Node $node, Scope $scope) use ($class, &$returns): void {
                    if (
                        ! $node instanceof Return_
                        || $node->expr === null
                        || $scope->isInAnonymousFunction()
                        || $scope->getFunctionName() !== 'rules'
                        || $scope->getClassReflection()?->getName() !== $class->getName()
                    ) {
                        return;
                    }

                    $returns = [...$returns, ...$this->ruleParser->returns($node->expr, $scope)];
                },
            );

            break;
        }

        if ($returns === []) {
            return null;
        }

        [$fields, $open] = $returns[0];

        foreach (array_slice($returns, 1) as [$other, $otherOpen]) {
            // A field only some return statements have rules for may or may not be validated, like its children.
            $partial = array_diff_key($fields, $other) + array_diff_key($other, $fields);
            $roots   = array_flip(array_map(static fn (string $name): string => RuleParser::segments($name)[0], array_keys($partial)));
            $open    = $open || $otherOpen || $partial !== [];

            foreach ($fields as $name => $readings) {
                if (isset($roots[RuleParser::segments($name)[0]])) {
                    unset($fields[$name]);
                } elseif ($readings !== null) {
                    $fields[$name] = $other[$name] === null ? null : [...$readings, ...$other[$name]];
                }
            }
        }

        $summaries = [];

        foreach ($fields as $name => $readings) {
            $field = RuleTypes::field($readings);

            if ($field === null) {
                $summaries = [];
                $open      = true;

                break;
            }

            $summaries[$name] = $field;
        }

        $tree = new RuleTree($summaries);

        return [$tree->shape(DataView::Validated, $open), $tree->shape(DataView::Input, true)];
    }
}

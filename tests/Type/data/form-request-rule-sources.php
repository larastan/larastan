<?php

declare(strict_types=1);

namespace FormRequestRuleSources;

use App\Http\Requests\BroadPhpDocDirectRequest;
use App\Http\Requests\BroadPhpDocSpreadRequest;
use App\Http\Requests\CollectionSelectionRequest;
use App\Http\Requests\ComputedRulesRequest;
use App\Http\Requests\DifferentArrayReturnsRequest;
use App\Http\Requests\DifferentConditionalReturnsRequest;
use App\Http\Requests\ExactPhpDocDirectRequest;
use App\Http\Requests\IntegerKeyRulesRequest;
use App\Http\Requests\LoopBuiltRulesRequest;
use App\Http\Requests\MultipleReturnsRequest;
use App\Http\Requests\NestedReturnsRequest;
use App\Http\Requests\NumericSpreadRulesRequest;
use App\Http\Requests\OptionalAncestorRulesRequest;
use App\Http\Requests\RootWildcardRulesRequest;
use App\Http\Requests\RootWildcardWithSiblingRulesRequest;
use App\Http\Requests\StaticRegistryRequest;
use App\Http\Requests\TraitRules\IntegerRequest;
use App\Http\Requests\UnknownAncestorKeyRequest;
use App\Http\Requests\UnpackedRulesRequest;
use App\Http\Requests\WildcardAncestorRulesRequest;
use FormRequestLifecycle\ExactRulesRequest;
use FormRequestLifecycle\InheritedRulesRequest;
use FormRequestLifecycle\ParentCompositionRequest;

use function PHPStan\Testing\assertType;

function testRuleSources(
    ExactRulesRequest $exact,
    InheritedRulesRequest $inherited,
    IntegerRequest $trait,
    UnpackedRulesRequest $unpacked,
    UnknownAncestorKeyRequest $unknownAncestorKey,
    NumericSpreadRulesRequest $numericSpread,
    OptionalAncestorRulesRequest $optionalAncestor,
    WildcardAncestorRulesRequest $wildcardAncestor,
    RootWildcardRulesRequest $rootWildcard,
    RootWildcardWithSiblingRulesRequest $rootWildcardWithSibling,
    MultipleReturnsRequest $multiple,
    DifferentArrayReturnsRequest $differentArrays,
    DifferentConditionalReturnsRequest $differentConditions,
    NestedReturnsRequest $nested,
    ParentCompositionRequest $parentComposition,
    ExactPhpDocDirectRequest $exactPhpDocDirect,
    BroadPhpDocDirectRequest $broadPhpDocDirect,
    BroadPhpDocSpreadRequest $broadPhpDocSpread,
    StaticRegistryRequest $staticRegistry,
    CollectionSelectionRequest $collectionSelection,
    ComputedRulesRequest $computed,
    LoopBuiltRulesRequest $loopBuilt,
    IntegerKeyRulesRequest $integerKeys,
): void {
    assertType('non-empty-string', $exact->exact);
    assertType('mixed', $exact->unrelated);
    assertType('non-empty-string', $inherited->exact);
    assertType('float|int|numeric-string', $trait->fromTrait);

    assertType('non-empty-string', $unpacked->constant);
    assertType('mixed', $unpacked->overwritten);
    assertType('float|int|numeric-string', $unpacked->stable);
    assertType('mixed', $unpacked->dynamicOnly);
    assertType(
        "array{constant: non-empty-string, stable: float|int|numeric-string, stableString: non-empty-string, explicitParent: array{name: non-empty-string}, 'v1.0': non-empty-string, ...}",
        $unpacked->validated(),
    );

    assertType('mixed', $unpacked->spreadOverwritten);
    assertType('non-empty-string', $unpacked->stableString);

    assertType('mixed', $unpacked->parent);
    assertType('non-empty-string', $unpacked->{'v1.0'});
    assertType('mixed', $unknownAncestorKey->parent);
    assertType('non-empty-string', $unknownAncestorKey->stable);
    assertType('array{stable: non-empty-string, ...}', $unknownAncestorKey->validated());
    assertType('non-empty-string', $numericSpread->before);
    assertType('array{name: non-empty-string, ...}', $numericSpread->parent);
    assertType('array{before: non-empty-string, parent: array{name: non-empty-string}, email: non-empty-string, ...}', $numericSpread->validated());
    assertType('array{name: non-empty-string, ...}', $unpacked->explicitParent);
    assertType('mixed', $optionalAncestor->parent);
    assertType('array{stable: non-empty-string, ...}', $optionalAncestor->validated());
    assertType('mixed', $multiple->parent);
    assertType('mixed', $wildcardAncestor->parent);
    assertType('array{name: non-empty-string, ...}', $wildcardAncestor->unrelated);
    assertType('array{unrelated: array{name: non-empty-string}, ...}', $wildcardAncestor->validated());

    assertType('mixed', $rootWildcard->{'*'});
    assertType('array', $rootWildcard->validated());
    assertType('mixed', $rootWildcard->validated('0.name'));
    assertType('array<string, mixed>', $rootWildcard->safe(['0.name']));
    assertType('mixed', $rootWildcardWithSibling->payload);
    assertType('array', $rootWildcardWithSibling->validated());

    assertType('non-empty-string', $multiple->shared);
    assertType('float|int|non-empty-string', $multiple->different);
    assertType('mixed', $multiple->firstOnly);
    assertType('mixed', $multiple->secondOnly);

    assertType('non-empty-string', $multiple->validated('record.name'));
    assertType('array', $differentArrays->validated());
    assertType('array{name?: string, other?: mixed}|null', $multiple->validated('pruned'));
    assertType('array', $differentConditions->validated());

    assertType('non-empty-string', $nested->actual);
    assertType('mixed', $nested->closure);
    assertType('mixed', $nested->function);
    assertType('mixed', $nested->nestedClass);
    assertType('array{actual: non-empty-string}', $nested->validated());

    assertType('mixed', $parentComposition->exact);
    assertType('mixed', $parentComposition->composed);
    assertType('float|int|numeric-string', $exactPhpDocDirect->age);
    assertType('non-empty-string', $exactPhpDocDirect->name);
    assertType('non-empty-string', $numericSpread->email);
    assertType('mixed', $broadPhpDocDirect->anything);
    assertType('mixed', $broadPhpDocSpread->broadOnly);
    assertType('non-empty-string', $broadPhpDocSpread->stable);
    assertType('mixed', $staticRegistry->registry);
    assertType('mixed', $collectionSelection->selected);

    assertType('non-empty-string', $computed->constantKey);
    assertType('mixed', $computed->dynamicConcatenation);
    assertType('mixed', $computed->ternary);
    assertType('mixed', $computed->coalesce);
    assertType('float|int|numeric-string', $computed->stableComputedSibling);
    assertType(
        'array{constantKey: non-empty-string, dynamicConcatenation?: mixed, ternary?: mixed, coalesce?: mixed, stableComputedSibling: float|int|numeric-string, ...}',
        $computed->validated(),
    );
    assertType('mixed', $loopBuilt->anything);

    assertType('array{shared: non-empty-string, different: float|int|non-empty-string, payload: array{name?: mixed}, record: array{name: non-empty-string}, pruned?: array{name?: string, other?: mixed}, ...}', $multiple->validated());

    assertType('mixed', $integerKeys->{'0'});
    assertType('mixed', $integerKeys->{'1'});
    assertType('array', $integerKeys->validated());
    assertType('mixed', $integerKeys->validated(0));
}

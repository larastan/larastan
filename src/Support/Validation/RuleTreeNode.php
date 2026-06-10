<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

/** @internal */
final class RuleTreeNode
{
    public const WILDCARD = '*';

    public ValidationRule|null $rule = null;

    /** @var array<string, RuleTreeNode> */
    public array $children = [];

    /** Contains an unsupported construct (numeric index, mixed wildcard/named level) — widen instead of guessing a shape. */
    public bool $degraded = false;
}

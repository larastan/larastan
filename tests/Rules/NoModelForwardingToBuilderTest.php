<?php

declare(strict_types=1);

namespace Tests\Rules;

use Larastan\Larastan\Rules\NoModelForwardingToBuilder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoModelForwardingToBuilder> */
class NoModelForwardingToBuilderTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoModelForwardingToBuilder();
    }

    public function testRuleDetectsModelForwardingToBuilderInstance(): void
    {
        $this->analyse([__DIR__ . '/data/NoModelForwardingToBuilderInstance.php'], [
            ["Method [first] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::first()], [::query()->first()] or [->newQuery()->first()] instead.", 5],
            ["Method [get] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::get()], [::query()->get()] or [->newQuery()->get()] instead.", 6],
            ["Method [find] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::find()], [::query()->find()] or [->newQuery()->find()] instead.", 7],
            ["Method [paginate] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::paginate()], [::query()->paginate()] or [->newQuery()->paginate()] instead.", 8],
            ["Method [where] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::where()], [::query()->where()] or [->newQuery()->where()] instead.", 9],
            ["Method [take] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::take()], [::query()->take()] or [->newQuery()->take()] instead.", 10],
            ["Method [max] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::max()], [::query()->max()] or [->newQuery()->max()] instead.", 11],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

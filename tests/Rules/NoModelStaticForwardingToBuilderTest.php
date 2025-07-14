<?php

declare(strict_types=1);

namespace Rules;

use Larastan\Larastan\Rules\NoModelStaticForwardingToBuilder;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoModelStaticForwardingToBuilder> */
class NoModelStaticForwardingToBuilderTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoModelStaticForwardingToBuilder();
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/NoModelStaticForwardingToBuilderInstance.php'], [
            ["Static method [first] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->first()] instead.", 3],
            ["Static method [get] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->get()] instead.", 4],
            ["Static method [find] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->find()] instead.", 5],
            ["Static method [paginate] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->paginate()] instead.", 6],
            ["Static method [where] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->where()] instead.", 7],
            ["Static method [take] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->take()] instead.", 8],
            ["Static method [max] is forwarded to a Builder instance, which is not allowed.\n    💡 Use [::query()->max()] instead.", 9],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../phpstan-tests.neon'];
    }
}

<?php

declare(strict_types=1);

namespace Tests\Rules;

use Illuminate\Filesystem\Filesystem;
use Larastan\Larastan\Collectors\UsedTranslationFacadeCollector;
use Larastan\Larastan\Collectors\UsedTranslationFunctionCollector;
use Larastan\Larastan\Collectors\UsedTranslationTranslatorCollector;
use Larastan\Larastan\Collectors\UsedTranslationViewCollector;
use Larastan\Larastan\Rules\NoMissingTranslationsRule;
use Larastan\Larastan\Support\ViewFileHelper;
use Larastan\Larastan\Support\ViewParser;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<NoMissingTranslationsRule> */
class NoMissingTranslationsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $viewParser     = new ViewParser($this->getContainer()->getService('currentPhpVersionSimpleDirectParser'));
        $viewFileHelper = new ViewFileHelper([], $this->getFileHelper());

        return new NoMissingTranslationsRule(new UsedTranslationViewCollector($viewParser, $viewFileHelper), new Filesystem(), []);
    }

    /** @return array<Collector<Node, mixed>> */
    protected function getCollectors(): array
    {
        return [
            new UsedTranslationFunctionCollector(),
            new UsedTranslationTranslatorCollector(),
            new UsedTranslationFacadeCollector(),
        ];
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/data/Translation.php'], [
            [
                'Translation "messages.test" has not been found.',
                18,
            ],
            [
                'Translation "messages.test" has not been found.',
                19,
            ],
            [
                'Translation "messages.test" has not been found.',
                20,
            ],
            [
                'Translation "messages.test" has not been found.',
                25,
            ],
            [
                'Translation "messages.test" has not been found.',
                26,
            ],
            [
                'Translation "messages.test" has not been found.',
                31,
            ],
            [
                'Translation "messages.test" has not been found.',
                32,
            ],
            [
                'Translation "sub/lines.farewell" has not been found.',
                38,
            ],
        ]);
    }

    /** @return string[] */
    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/phpstan-rules.neon',
        ];
    }
}

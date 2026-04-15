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
use PhpParser\Node\Expr\CallLike;
use PHPStan\Collectors\Collector;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

use const PHP_EOL;

/** @extends RuleTestCase<NoMissingTranslationsRule> */
class NoMissingTranslationsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $viewParser     = new ViewParser($this->getContainer()->getService('currentPhpVersionSimpleDirectParser'));
        $viewFileHelper = new ViewFileHelper([], $this->getFileHelper());

        return new NoMissingTranslationsRule(new UsedTranslationViewCollector($viewParser, $viewFileHelper), new Filesystem(), []);
    }

    /** @return array<Collector<CallLike, mixed>> */
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
        /** @see ../application/resources/views/translations.blade.php */
        $errorsFromBladeFile = [
            ['Translation "lang.get" has not been found.', 26],
            ['Translation "lang.choice" has not been found.', 27],
            ['Translation "lang.trans" has not been found.', 28],
            ['Translation "lang.transChoice" has not been found.', 29],

            ['Translation "a.b" has not been found.', 31],
            ['Translation "a.b.c" has not been found.', 32],
            ['Translation "a_b.c-d" has not been found.', 33],
            ['Translation "a.b" has not been found.', 34],
            ['Translation "a.b!" has not been found.', 35],
            ['Translation "a.translate me!" has not been found.', 36],
            ['Translation "a.über~" has not been found.', 37],
            ['Translation "app.i\\\'m" has not been found.', 38],
            ['Translation "app.i\'m" has not been found.', 39],
            ['Translation "app.\\"ok\\"" has not been found.', 40],
            ['Translation "app."ok"" has not been found.', 41],
            ['Translation "a.b" has not been found.', 42],

            ['Translation "directive.lang" has not been found.', 44],
            ['Translation "directive.choice" has not been found.', 45],

            ['Translation "surrounded.by.text" has not been found.', 47],
            ['Translation "surrounded.by.html.tags" has not been found.', 48],

            ['Translation "trans.uppercase" has not been found.', 50],
            ['Translation "object.instance.method" has not been found.', 51],
            ['Translation "dollar.t" has not been found.', 52],
            ['Translation "double.underscore.helper.function" has not been found.', 53],

            ['Translation "lang.in.html.attribute" has not been found.', 55],
            ['Translation "lang.with.extra.paramater" has not been found.', 56],
            ['Translation "lang.in.blade.comment" has not been found.', 57],
            ['Translation "lang.in.html.comment" has not been found.', 58],
            ['Translation "and.a.simple.translation.afterwards" has not been found.', 59],
            ['Translation "lang.with.prefix" has not been found.', 60],

            ['Translation "lang.with.new' . "\n" . 'line.char" has not been found.', 71],
            ['Translation "lang.spanning' . PHP_EOL . 'multiple.lines" has not been found.', 72],
            ['Translation "lang.with.a.back\\slash.single.quotes" has not been found.', 74],
            ['Translation "lang.with.a.back\\slash.double.quotes" has not been found.', 75],
            ['Translation "lang.with.a.hex.char" has not been found.', 76],
        ];

        $this->analyse([__DIR__ . '/data/Translation.php'], [
            ...$errorsFromBladeFile,
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
                40,
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

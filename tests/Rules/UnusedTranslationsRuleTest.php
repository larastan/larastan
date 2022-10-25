<?php

namespace Rules;

use Illuminate\Filesystem\Filesystem;
use NunoMaduro\Larastan\Collectors\UsedTranslationFunctionsCollector;
use NunoMaduro\Larastan\Collectors\UsedTranslationsInTranslatorCollector;
use NunoMaduro\Larastan\Collectors\UsedTranslationsInViewsCollector;
use NunoMaduro\Larastan\Collectors\UsedTranslationsLangFacadeCollector;
use NunoMaduro\Larastan\Rules\UnusedTranslationsRule;
use NunoMaduro\Larastan\Support\ViewFileHelper;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/** @extends RuleTestCase<UnusedTranslationsRule> */
class UnusedTranslationsRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        $viewFileHelper = new ViewFileHelper([__DIR__.'/../Application/resources/views'], $this->getFileHelper());

        return new UnusedTranslationsRule(new UsedTranslationsInViewsCollector(
            $this->getContainer()->getService('currentPhpVersionSimpleDirectParser'),
            $viewFileHelper,
        ), [], new Filesystem());
    }

    protected function getCollectors(): array
    {
        return [
            new UsedTranslationFunctionsCollector(),
            new UsedTranslationsLangFacadeCollector(),
            new UsedTranslationsInTranslatorCollector(),
        ];
    }

//    protected function setUp(): void
//    {
//        parent::setUp();
//
//        // This is a workaround for a weird PHPStan container cache issue.
//        require __DIR__.'/../../bootstrap.php';
//    }

    public function testRule(): void
    {
        $this->analyse([__DIR__.'/Data/TranslationsController.php'], [
            [
                '"system.baz" translation is not used in the project.',
                00,
            ],
        ]);
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__.'/../../extension.neon',
        ];
    }
}

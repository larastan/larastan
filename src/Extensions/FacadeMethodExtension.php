<?php

declare(strict_types=1);

/**
 * This file is part of Laravel Code Analyse.
 *
 * (c) Nuno Maduro <enunomaduro@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace NunoMaduro\LaravelCodeAnalyse\Extensions;

use function get_class;
use Illuminate\Support\Facades\Facade;
use PHPStan\Reflection\ClassReflection;
use NunoMaduro\LaravelCodeAnalyse\FacadeConcreteClassResolver;

final class FacadeMethodExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected $staticAccess = true;

    /**
     * {@inheritdoc}
     */
    protected function subject(): string
    {
        return Facade::class;
    }

    /**
     * {@inheritdoc}
     */
    protected function searchIn(ClassReflection $classReflection): array
    {
        $facadeClass = $classReflection->getName();

        if ($concrete = $facadeClass::getFacadeRoot()) {
            return [get_class($concrete)];
        }

        return [NullConcreteClass::class];
    }
}

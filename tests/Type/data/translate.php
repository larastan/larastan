<?php

declare(strict_types=1);

namespace EloquentBuilder;

use function PHPStan\Testing\assertType;

assertType('Illuminate\Contracts\Translation\Translator', trans());
assertType('string', trans('foo'));
assertType('string', trans('Hi :name', ['name' => 'Niek']));

assertType('null', __());
assertType('string', __('foo'));
assertType('string', __('Hi :name', ['name' => 'Niek']));

/** @var 'language.string'|'language.array' $key */
assertType('string', trans('language.string'));
assertType('string', trans('language.absent'));
assertType('array', trans('language.array'));
assertType('string', trans('language.array.key'));
assertType('array|string', trans($key));

assertType('string', __('language.string'));
assertType('string', __('language.absent'));
assertType('array', __('language.array'));
assertType('string', __('language.array.key'));
assertType('array|string', __($key));

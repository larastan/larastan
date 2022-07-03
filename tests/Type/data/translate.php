<?php

declare(strict_types=1);

namespace EloquentBuilder;

use function PHPStan\Testing\assertType;

assertType('Illuminate\Contracts\Translation\Translator', trans());
assertType('mixed', trans('foo'));
assertType('mixed', trans('Hi :name', ['name' => 'Niek']));

assertType('null', __());
assertType('mixed', __('foo'));
assertType('mixed', __('Hi :name', ['name' => 'Niek']));

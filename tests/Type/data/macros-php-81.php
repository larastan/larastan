<?php

namespace MacrosPHP81;

use Illuminate\Database\Eloquent\Builder;

use function PHPStan\Testing\assertType;

assertType('string', Builder::macroWithEnumDefaultValue());

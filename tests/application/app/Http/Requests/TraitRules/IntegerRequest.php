<?php

declare(strict_types=1);

namespace App\Http\Requests\TraitRules;

use App\Traits\FormRequestRules\ProvidesRules;
use Illuminate\Foundation\Http\FormRequest;

class IntegerRequest extends FormRequest
{
    use ProvidesRules;

    final protected const FIELD_TYPE = 'integer';
}

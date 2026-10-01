<?php

declare(strict_types=1);

namespace App\Http\Requests\TraitRules;

use App\Traits\FormRequestRules\ProvidesRules;
use Illuminate\Foundation\Http\FormRequest;

const FIELD_RULE = 'required|integer';
const IMPORTED_RULE = 'required|integer';

/** @return 'required|integer' */
function importedRule(): string
{
    return 'required|integer';
}

class StringRequest extends FormRequest
{
    use ProvidesRules;

    final protected const FIELD_TYPE = 'string';
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\RuleRegistryModel;
use Illuminate\Foundation\Http\FormRequest;

class ExactPhpDocDirectRequest extends FormRequest
{
    public function rules(): array
    {
        return RuleRegistryModel::exactValidationRules();
    }
}

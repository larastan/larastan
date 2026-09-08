<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

class RuleRegistryModel extends Model
{
    /** @return array{age: array{'integer', 'required'}, name: 'required|string'} */
    public static function exactValidationRules(): array
    {
        return [
            'age' => ['integer', 'required'],
            'name' => 'required|string',
        ];
    }

    /** @return array<string, string> */
    public static function validationRules(): array
    {
        return ['registry' => 'required|string'];
    }
}

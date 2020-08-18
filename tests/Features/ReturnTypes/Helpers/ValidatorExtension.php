<?php

declare(strict_types=1);

namespace Tests\Features\ReturnTypes\Helpers;

use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;

class ValidatorExtension
{
    public function testWithNoArgument(): Factory
    {
        return validator();
    }

    public function testWithArgument(): Validator
    {
        return validator(['foo' => 'bar'], ['foo' => 'required']);
    }

    /** @return array<string, mixed> */
    public function testValid(): array
    {
        return validator(['foo' => 'bar'], ['foo' => 'required'])->valid();
    }

    public function testIssue638(Request $request): Validator
    {
        $validator = validator($request->all(), [
            'message' => 'required',
        ]);

        $validationAttributeNames = [
            'message' => trans('g.label.message'),
        ];

        $validator->setAttributeNames($validationAttributeNames);
        $validator->validate();

        return $validator;
    }
}

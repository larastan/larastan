<?php

namespace FormRequestPrematureAccess;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\ValidatedInput;

class EarlyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->validated();
        $this->safe()->only(['email']);
        $alias = $this;
        $alias->validated();
        $this->validated(...);
        $method = 'validated';
        $this->$method();
        parent::validated();
        $callback = fn () => $this->validated();
        (function () { $this->safe(); })();
    }

    public function authorize(): bool
    {
        $this->validated(default: false);
        return true;
    }

    public function rules(): array
    {
        $this->safe();
        return ['email' => 'string'];
    }

    public function validationData(): array
    {
        $this->validated(...['email']);
        return [];
    }

    public function messages(): array
    {
        $this->validated('email');
        return [];
    }

    public function attributes(): array
    {
        $this->SAFE();
        return [];
    }

    public function withValidator(Validator $validator): void
    {
        $this->setValidator($validator);
        $this->validated();
        if ($this->validator !== null) {
            $this->safe();
        }
        $validator->after(fn () => $this->validated());
    }

    public function after(): array
    {
        $this->validated();
        return [function () { $this->safe(); }];
    }

    protected function passedValidation(): void
    {
        $this->validated();
        $this->safe();
    }

    public function helper(EarlyRequest $other): void
    {
        $this->validated();
        $other->validated();
    }
}

trait CustomMethods
{
    public function validated($key = null, $default = null): array
    {
        return [];
    }

    public function safe(?array $keys = null): ValidatedInput|array
    {
        return [];
    }
}

class CustomRequest extends FormRequest
{
    use CustomMethods;

    public function rules(): array
    {
        $this->validated();
        $this->safe();
        return [];
    }
}

class InheritedCustomRequest extends CustomRequest
{
    public function authorize(): bool
    {
        $this->validated();
        $this->safe();
        return true;
    }
}

class OtherObject
{
    public function rules(EarlyRequest $request): array
    {
        $request->validated();
        return [];
    }
}

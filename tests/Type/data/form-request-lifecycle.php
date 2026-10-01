<?php

declare(strict_types=1);

namespace FormRequestLifecycle;

use App\Contracts\RequestMarker;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

class ExactRulesRequest extends FormRequest
{
    public function rules(): array
    {
        assertType('mixed', $this->exact);

        return ['exact' => 'required|string'];
    }

    public function authorize(): bool
    {
        assertType('mixed', $this->exact);

        return true;
    }

    protected function prepareForValidation(): void
    {
        assertType('mixed', $this->exact);
        assertType('mixed', $this->input('exact'));
        assertType('int', $this->integer('exact'));
        assertType('bool', $this->boolean('exact'));

        $alias = $this;
        assertType('mixed', $alias->exact);
        assertType('mixed', $alias->input('exact'));

        if (is_string($alias->exact)) {
            assertType('string', $alias->exact);
        }

        $other = new ExactRulesRequest();
        assertType('non-empty-string', $other->exact);

        if ($this instanceof RequestMarker) {
            assertType('mixed', $this->exact);

            $intersectionAlias = $this;
            assertType('mixed', $intersectionAlias->exact);
        }

        if (is_string($this->exact)) {
            assertType('string', $this->exact);
        }

        (function (): void {
            assertType('mixed', $this->exact);
            assertType('mixed', $this->input('exact'));
        })();
    }

    public function isPrecognitive(): bool
    {
        assertType('mixed', $this->exact);

        return parent::isPrecognitive();
    }

    public function filterPrecognitiveRules($rules)
    {
        assertType('mixed', $this->exact);

        return parent::filterPrecognitiveRules($rules);
    }

    protected function passedValidation(): void
    {
        assertType('non-empty-string', $this->exact);
        assertType('non-empty-string', $this->input('exact'));
        assertType('bool', $this->boolean('exact'));

        (function (): void {
            assertType('non-empty-string', $this->exact);
            assertType('non-empty-string', $this->input('exact'));
        })();
    }

    public function unrelated(): array
    {
        return ['unrelated' => 'required|integer'];
    }

    public function toDto(): string
    {
        assertType('non-empty-string', $this->exact);
        assertType('non-empty-string', $this->input('exact'));

        return $this->exact;
    }
}

/** @property int $exact */
class AnnotatedRulesRequest extends ExactRulesRequest
{
    public function authorize(): bool
    {
        assertType('int', $this->exact);

        $alias = $this;
        assertType('int', $alias->exact);

        return true;
    }
}

class InheritedRulesRequest extends ExactRulesRequest
{
}

class ParentCompositionRequest extends ExactRulesRequest
{
    public function rules(): array
    {
        return parent::rules() + ['composed' => 'required|string'];
    }
}

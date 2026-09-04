<?php

declare(strict_types=1);

namespace ValidationRules;

use App\Casts\BackedEnumeration;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Stringable;

use function PHPStan\Testing\assertType;

enum UnitEnumeration
{
    case Foo;
    case Bar;
}

final class StringableValue implements Stringable
{
    public function __toString(): string
    {
        return 'foo';
    }
}

/** @implements Arrayable<int, int|string> */
final class RuleValues implements Arrayable
{
    /** @return array{1, 'foo'} */
    public function toArray(): array
    {
        return [1, 'foo'];
    }
}

final class AdditionalRulesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'dateValue' => ['required', Rule::date()],
            'formattedDate' => ['required', Rule::date()->format('Y-m-d')],
            'emailValue' => ['required', Rule::email()],
            'dimensionsValue' => ['required', Rule::dimensions()->maxWidth(1920)],
            'fileValue' => ['required', Rule::file()],
            'imageValue' => ['required', Rule::imageFile()],
            'passwordValue' => ['required', Password::min(8)->letters()->numbers()],
        ];
    }
}

/** @param array{'draft'}|'published' $arrayOrString */
function test(mixed $mixed, array|string $arrayOrString, AdditionalRulesRequest $request): void
{
    assertType("Illuminate\\Validation\\Rules\\In<array{'foo', 'bar'}>", Rule::in(['foo', 'bar']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{'name', 'email'}>", Rule::array(['name', 'email']));

    assertType("Illuminate\\Validation\\Rules\\In<array{1, 1.5, true, false, null, 'foo'}>", Rule::in([1, 1.5, true, false, null, 'foo']));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 1.5, true, false, null, 'foo'}>", Rule::array([1, 1.5, true, false, null, 'foo']));
    assertType('Illuminate\\Validation\\Rules\\In<array{ValidationRules\\UnitEnumeration::Foo, ValidationRules\\StringableValue}>', Rule::in([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{ValidationRules\\UnitEnumeration::Foo, ValidationRules\\StringableValue}>', Rule::array([UnitEnumeration::Foo, new StringableValue()]));
    assertType('Illuminate\\Validation\\Rules\\In<array{ValidationRules\\UnitEnumeration::Foo}>', Rule::in(UnitEnumeration::Foo));
    assertType('Illuminate\\Validation\\Rules\\In<array{1, mixed}>', Rule::in([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{1, mixed}>', Rule::array([1, $mixed]));
    assertType('Illuminate\\Validation\\Rules\\In<array>', Rule::in($arrayOrString));
    assertType('Illuminate\\Validation\\Rules\\In<array<int, mixed>>', Rule::in(...[['draft']]));
    assertType("Illuminate\\Validation\\Rules\\In<array{'draft'}>", Rule::in(['draft'], 'ignored'));
    assertType("Illuminate\\Validation\\Rules\\In<array{1, 'foo'}>", Rule::in(new RuleValues()));
    assertType("Illuminate\\Validation\\Rules\\ArrayRule<array{1, 'foo'}>", Rule::array(new RuleValues()));
    assertType('Illuminate\\Validation\\Rules\\ArrayRule<array{}>', Rule::array());

    assertType("Illuminate\\Validation\\Rules\\Enum<'App\\\\Casts\\\\BackedEnumeration'>", Rule::enum(BackedEnumeration::class));
    assertType("Illuminate\\Validation\\Rules\\Enum<'ValidationRules\\\\UnitEnumeration'>", Rule::enum(UnitEnumeration::class));

    assertType('Illuminate\\Validation\\Rules\\Numeric<(float|int|numeric-string)>', Rule::numeric());
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->digits(3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->digitsBetween(1, 3));
    assertType('Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>', Rule::numeric()->exactly(3));
    assertType(
        'Illuminate\\Validation\\Rules\\Numeric<(int|numeric-string)>',
        Rule::numeric()
            ->integer()
            ->between(1, 10)
            ->decimal(2, 4)
            ->different('other')
            ->greaterThan('minimum')
            ->greaterThanOrEqualTo('minimum')
            ->lessThan('maximum')
            ->lessThanOrEqualTo('maximum')
            ->max(10)
            ->maxDigits(3)
            ->min(1)
            ->minDigits(1)
            ->multipleOf(0.5)
            ->same('confirmation'),
    );

    assertType('Illuminate\\Validation\\Rules\\Date<DateTimeInterface|float|int|string>', Rule::date());
    assertType(
        'Illuminate\\Validation\\Rules\\Date<float|int|string>',
        Rule::date()->format('Y-m-d')->beforeToday(),
    );

    assertType('DateTimeInterface|float|int|string', $request->dateValue);
    assertType('float|int|string', $request->formattedDate);
    assertType('string|Stringable', $request->emailValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->dimensionsValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->fileValue);
    assertType('Illuminate\\Http\\UploadedFile', $request->imageValue);
    assertType('string', $request->passwordValue);
}

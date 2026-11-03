<?php

namespace ModelCastsParsing;

use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use function PHPStan\Testing\assertType;

function test(ModelWithCasts $model, OtherModelWithCasts $other, ConditionalModelWithCasts $conditional, DynamicValueModelWithCasts $dynamic, PartiallyDynamicModelWithCasts $partial, PhpDocUnknownCastsModel $annotated, PhpDocOptionalCastsModel $optional, SelfConstantCastsModel $self, ChildInheritedCastsModel $inherited, TraitBoolCastModel $traitBool, TraitStringCastModel $traitString, TraitInheritedCastModel $traitInherited, ExternalTraitCastModel $externalTrait, AttributedCastModel $attributed, TraitAliasedCastsModel $aliasedTrait, LateStaticOverrideCastsModel $lateBound, DynamicKeyCastsModel $dynamicKey, TimestampStringCastsModel $timestamp, CastedKeyWithoutSchemaModel $keyed): void
{
    assertType('bool', $model->integer);
    assertType(Stringable::class, $model->string);

    // Two casts() definitions in the same file must not be confused.
    assertType('string', $other->integer);
    assertType('int', $other->string);

    // Conditional arrays can override schema types; do not assume int.
    assertType('mixed', $conditional->integer);

    // casts() overrides $casts: an unresolved override is mixed, not bool.
    assertType('mixed', $dynamic->integer);
    assertType('mixed', $dynamic->toArray()['integer']);
    assertType('string', $dynamic->string);

    // Resolve known entries independently from dynamic values.
    assertType('mixed', $partial->integer);
    assertType(Stringable::class, $partial->string);

    // A PHPDoc array shape is not necessarily a literal cast value.
    assertType('mixed', $annotated->integer);

    // Optional casts() keys can override a conflicting $casts value.
    assertType('mixed', $optional->integer);

    // Evaluate class constants in the model declaration, not file scope.
    assertType('bool', $self->integer);
    assertType('bool', $inherited->integer);

    // A model may import casts() through a trait. The same trait method
    // must resolve self:: against each consuming model, not the trait.
    assertType('bool', $traitBool->integer);
    assertType('string', $traitString->integer);
    assertType('bool', $traitInherited->integer);
    assertType('bool', $externalTrait->integer);
    assertType(Stringable::class, $externalTrait->string);

    // ReflectionMethod reports the function line, while parsers can
    // include attribute lines in the AST node's source span.
    assertType('bool', $attributed->integer);

    // ReflectionMethod names may differ from the trait method source due to aliasing.
    assertType('bool', $aliasedTrait->integer);

    // static:: may resolve to a different cast in an inherited subclass.
    assertType('mixed', $lateBound->integer);

    // A computed cast key can replace ANY existing $casts or schema key.
    assertType('mixed', $dynamicKey->integer);
    assertType('mixed', $dynamicKey->string);
    assertType('mixed', $dynamicKey->toArray()['integer']);

    // Laravel prioritizes casts() over the conventional date conversion.
    assertType('string|null', $timestamp->created_at);

    // An explicit id cast applies even when the migration has no table.
    assertType('string', $keyed->id);
}

class CastedKeyWithoutSchemaModel extends Model
{
    protected $table = 'native_casted_key_without_migration';

    protected function casts(): array
    {
        return ['id' => 'string'];
    }
}

class TimestampStringCastsModel extends Model
{
    protected $table = 'users';

    protected function casts(): array
    {
        return ['created_at' => 'string'];
    }
}

class DynamicKeyCastsModel extends Model
{
    protected $table = 'model_with_casts';

    protected $casts = ['integer' => 'bool'];

    protected function casts(): array
    {
        return [$this->castColumn() => 'string'];
    }

    private function castColumn(): string
    {
        return 'integer';
    }
}

class SelfConstantCastsModel extends Model
{
    private const BOOLEAN_CAST = 'bool';

    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        return ['integer' => self::BOOLEAN_CAST];
    }
}

class LateStaticBaseCastsModel extends Model
{
    protected const CAST = 'bool';

    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        return ['integer' => static::CAST];
    }
}

class LateStaticOverrideCastsModel extends LateStaticBaseCastsModel
{
    protected const CAST = 'string';
}

trait CastsViaTrait
{
    protected function casts(): array
    {
        return ['integer' => self::MODEL_CAST];
    }
}

class TraitBoolCastModel extends Model
{
    use CastsViaTrait;

    protected const MODEL_CAST = 'bool';

    protected $table = 'model_with_casts';
}

class TraitStringCastModel extends Model
{
    use CastsViaTrait;

    protected const MODEL_CAST = 'string';

    protected $table = 'model_with_casts';
}

class TraitInheritedCastModel extends TraitBoolCastModel
{
}

class ExternalTraitCastModel extends Model
{
    use External\ExternalModelCastsTrait;

    protected $table = 'model_with_casts';
}

#[\Attribute(\Attribute::TARGET_METHOD)]
class CastsMarker
{
}

class AttributedCastModel extends Model
{
    protected $table = 'model_with_casts';

    #[CastsMarker]
    protected function casts(): array
    {
        return ['integer' => 'bool'];
    }
}

trait AliasedCastMethod
{
    protected function getTraitCasts(): array
    {
        return ['integer' => 'bool'];
    }
}

class TraitAliasedCastsModel extends Model
{
    use AliasedCastMethod {
        getTraitCasts as casts;
    }

    protected $table = 'model_with_casts';
}

class BaseInheritedCastsModel extends Model
{
    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        return ['integer' => 'bool'];
    }
}

class ChildInheritedCastsModel extends BaseInheritedCastsModel
{
}

class PhpDocOptionalCastsModel extends Model
{
    protected $table = 'model_with_casts';

    protected $casts = ['integer' => 'bool'];

    /** @return array{integer?: string} */
    protected function casts(): array
    {
        if ($this->getConnectionName() === 'another') {
            return ['integer' => 'string'];
        }

        return [];
    }
}

class PhpDocUnknownCastsModel extends Model
{
    protected $table = 'model_with_casts';

    /** @return array{integer: string} */
    protected function casts(): array
    {
        return ['integer' => $this->selectCast()];
    }

    private function selectCast(): string
    {
        return 'string';
    }
}

class PartiallyDynamicModelWithCasts extends Model
{
    protected $table = 'model_with_casts';

    protected $casts = ['integer' => 'bool'];

    protected function casts(): array
    {
        return [
            'integer' => $this->selectCast(),
            'string' => AsStringable::class,
        ];
    }

    private function selectCast(): string
    {
        return 'string';
    }
}

class DynamicValueModelWithCasts extends Model
{
    protected $table = 'model_with_casts';

    protected $casts = ['integer' => 'bool'];

    protected function casts(): array
    {
        return ['integer' => $this->selectCast()];
    }

    private function selectCast(): string
    {
        return 'string';
    }
}

class ConditionalModelWithCasts extends Model
{
    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        if ($this->getConnectionName() === 'another') {
            return ['integer' => 'string'];
        }

        return ['integer' => 'bool'];
    }
}

class OtherModelWithCasts extends Model
{
    protected $table = 'model_with_casts';

    protected function casts(): array
    {
        return [
            'integer' => 'string',
            'string' => 'integer',
        ];
    }
}

class ModelWithCasts extends Model
{
    public function foo(): int
    {
        return 5;
    }

    public function casts(): array
    {
        return [
            'integer' => 'bool',
            'string' => AsStringable::class.':argument',
        ];
    }
}

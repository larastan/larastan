<?php

namespace ModelSerializationLaravel13;

use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

#[Appends('label', 'secret')]
#[Hidden(['secret', 'name'])]
#[Visible('id', 'name', 'label', 'secret')]
class AttributeSerializationModel extends Model
{
    protected $table = 'users';

    public function getLabelAttribute(): string
    {
        return 'label';
    }

    public function getSecretAttribute(): string
    {
        return 'secret';
    }
}

class InheritedAttributeSerializationModel extends AttributeSerializationModel
{
}

#[Hidden('label')]
class ReplacedAttributeSerializationModel extends AttributeSerializationModel
{
    protected $hidden = ['secret'];
}

function serialization(
    AttributeSerializationModel $model,
    InheritedAttributeSerializationModel $inherited,
    ReplacedAttributeSerializationModel $replaced,
): void {
    assertType('array{id?: int, label?: string, ...<string, mixed>}', $model->attributesToArray());
    assertType('array{id?: int, label?: string, ...<string, mixed>}', $inherited->toArray());
    assertType('array{id?: int, name?: string, ...<string, mixed>}', $replaced->attributesToArray());
}

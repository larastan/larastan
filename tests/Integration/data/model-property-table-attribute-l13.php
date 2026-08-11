<?php

namespace ModelPropertyTableAttribute;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('users')]
class TableAttributeModel extends Model
{
}

#[Table(keyType: 'string', incrementing: false)]
class StringKeyModel extends Model
{
}

function existingColumn(TableAttributeModel $model): string
{
    return $model->email;
}

function unknownColumn(TableAttributeModel $model): string
{
    return $model->not_a_column;
}

function keyType(StringKeyModel $model): int
{
    return $model->id;
}

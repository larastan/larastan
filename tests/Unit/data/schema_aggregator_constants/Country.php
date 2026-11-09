<?php

namespace Tests\Unit\SchemaAggregatorConstants;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $primaryKey = 'code';

    protected $keyType = 'string';

    public $incrementing = false;
}

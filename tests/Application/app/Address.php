<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $address_id
 * @property string $nullable_address_id
 */
class Address extends Model
{
    protected $keyType = 'uuid';
}

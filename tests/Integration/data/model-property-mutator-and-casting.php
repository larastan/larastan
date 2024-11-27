<?php

namespace ModelPropertyMutatorAndCasting;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Address
{
    public function __construct(
        public string $lineOne,
        public string $lineTwo,
    ) {}
}

class ModelPropertyMutatorAndCastingInClass extends \Illuminate\Database\Eloquent\Model
{
    /**
     * @return Attribute<Address,Address>
     */
    protected function address(): Attribute
    {
        return Attribute::make(
            get: fn(mixed $value, array $attributes) => new Address(
                $attributes['address_line_one'],
                $attributes['address_line_two'],
            ),
            set: fn(Address $value) => [
                'address_line_one' => $value->lineOne,
                'address_line_two' => $value->lineTwo,
            ],
        );
    }
}

<?php

namespace Bug2545;

use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model implements MissingInterface
{
    public function getId(): int
    {
        return $this->id;
    }
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ModelWithConflictingInitializer extends Model
{
    public bool $initializerCalled = false;

    public function initializeModelAttributes(): void
    {
        $this->initializerCalled = true;
    }
}

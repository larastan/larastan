<?php

namespace ModelSchemaDependency;

use App\User;

function usesModelProperty(User $user): string
{
    return $user->email;
}

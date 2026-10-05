<?php

namespace ModelPropertySchemaDependency;

use App\ModelPropertyConsumer;

function callsMethodWithModelPropertyParameter(ModelPropertyConsumer $consumer): void
{
    $consumer->orderBy('email');
}

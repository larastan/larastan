<?php

declare(strict_types=1);

namespace App\Http\Requests;

// Renaming the subclasses reverses PHPStan's normalized union order.
class AOverriddenApiRequest extends ZOverriddenApiRequest
{
}

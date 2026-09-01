<?php

namespace FacadesIntegration;

use Illuminate\Support\Facades\View;
use InvalidArgumentException;

function catchesRealException(): void
{
    try {
        $view = View::first(['a', 'b']);
    } catch (InvalidArgumentException $e) {
        return;
    }
}

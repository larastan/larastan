<?php

declare(strict_types=1);

namespace FacadeThrowType;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFacade;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class FacadeThrowType
{
    public function catchesRealException(): View|null
    {
        try {
            return ViewFacade::first(['a', 'b']);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    public function catchesFacadeRootNotSet(): View|null
    {
        try {
            return ViewFacade::first(['a', 'b']);
        } catch (RuntimeException $e) {
            return null;
        }
    }

    public function deadCatchIsStillReported(): View|null
    {
        try {
            return ViewFacade::first(['a', 'b']);
        } catch (JsonException $e) {
            return null;
        }
    }
}

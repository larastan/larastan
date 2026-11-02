<?php

if (PHP_SAPI === 'cli') {
    return ['v' => 'cli'];
}

return ['v' => 'web'];

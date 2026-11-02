<?php

$name = config('scopeself.name');

return [
    'name' => 'larastan',
    'early' => $name,
    'late' => config('scopeself.name'),
];

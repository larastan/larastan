<?php

$normalize = static function (string $value): string {
    return strtolower($value);
};

return [
    'v' => $normalize('A'),
];

<?php

declare(strict_types=1);

namespace RequestObject;

use function PHPStan\Testing\assertType;

/** @var \Illuminate\Http\Request $request */
assertType('array<int, Illuminate\Http\UploadedFile>', $request->file());
assertType('array<int, Illuminate\Http\UploadedFile>|Illuminate\Http\UploadedFile|null', $request->file('foo'));
assertType('array<int, Illuminate\Http\UploadedFile>|Illuminate\Http\UploadedFile|stdClass', $request->file('foo', new \stdClass()));

assertType('Illuminate\Routing\Route|null', $request->route());
assertType('object|string|null', $request->route('foo'));
assertType('object|string|null', $request->route('foo', 'bar'));

assertType('Illuminate\Foundation\Auth\User|null', $request->user());

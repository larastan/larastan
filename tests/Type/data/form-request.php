<?php

declare(strict_types=1);

namespace FormRequest;

use App\Http\Requests\FooRequest;
use Illuminate\Foundation\Http\FormRequest;

use function PHPStan\Testing\assertType;

function test(FormRequest $request, FooRequest $fooRequest): void
{
    assertType('Illuminate\Support\ValidatedInput', $request->safe());
    assertType('array{key: mixed}', $request->safe(['key']));
    assertType('array<string, mixed>', $request->validated());

    assertType('string', $fooRequest->name);
    assertType('int', $fooRequest->age);
    assertType("1|'1'|'on'|'true'|'yes'|true|null", $fooRequest->newsletter);
    assertType("'date'|'rating'", $fooRequest->type);
    assertType('0|1', $fooRequest->rating);
    assertType("'dash'|'john-d'|null", $fooRequest->nickname);
    assertType('float|int|numeric-string', $fooRequest->price);
    assertType("'asc'|'desc'|null", $fooRequest->sortOrder);
    assertType('array', $fooRequest->settings);
    assertType('array{name: string, surname?: string|null, nickname?: string, thing: mixed}', $fooRequest->author);
    assertType('array{display: array{mode: string}}', $fooRequest->options);
    assertType('array{theme?: string}|null', $fooRequest->prefs);
    assertType('array|null', $fooRequest->positions);
    assertType('list<string>|null', $fooRequest->tags);
    assertType('list<int|null>|null', $fooRequest->scores);
    assertType('list<array{email: string, age?: int, addresses?: list<array{city: string}>, address?: array{city: string}}>|null', $fooRequest->users);
    assertType('list<array{name: string}>', $fooRequest->guests);
    assertType('list<array{id: int}>|null', $fooRequest->accounts);
    assertType('string', $fooRequest->conflicted);
    assertType('list<array{origin: array{zip: string}|null}>|null', $fooRequest->shipping);
    assertType('string', $fooRequest->{'v1.0'});
    assertType('array|null', $fooRequest->flags);
    assertType('mixed', $fooRequest->{'author.name'});
    assertType('int<1, 20>|null', $fooRequest->limit);
    assertType('array{fragment: string|null, domain: string, path: string|null, port: mixed}', $fooRequest->url);
    assertType('mixed', $fooRequest->fallback);
    assertType('mixed', $fooRequest->dynamicRules);
}

<?php

namespace ModelTemplateClassString;

use App\Post;
use App\User;
use BuilderOfType\ModelWithNeverQuery;
use Illuminate\Database\Eloquent\Model;

use function PHPStan\Testing\assertType;

/**
 * @template TModel of Model
 * @template TPost of Post
 * @param  class-string<TModel>  $modelClass
 * @param  class-string<TPost>  $postClass
 * @param  TModel  $model
 */
function test(string $modelClass, string $postClass, Model $model): void
{
    assertType('TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)', $modelClass::findOrFail(1));
    assertType('TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)|null', $modelClass::find(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)>', $modelClass::find([1, 2]));
    assertType('TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)', $modelClass::create([]));
    assertType('TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)|null', $modelClass::where('id', 1)->first());
    assertType('Illuminate\Database\Eloquent\Collection<int, TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)>', $modelClass::whereKey(1)->get());
    assertType('int<0, max>', $modelClass::count());
    assertType('TModel of Illuminate\Database\Eloquent\Model (function ModelTemplateClassString\test(), argument)', $model::findOrFail(1));
    assertType('TPost of App\Post (function ModelTemplateClassString\test(), argument)', $postClass::findOrFail(1));
    assertType('App\PostBuilder<TPost of App\Post (function ModelTemplateClassString\test(), argument)>', $postClass::where('id', 1));
}

/**
 * @template TNeverQuery of ModelWithNeverQuery
 * @param  class-string<TNeverQuery>  $neverQueryClass
 * @param  class-string<Post>|class-string<User>  $postOrUserClass
 */
function testReceivers(string $neverQueryClass, string $postOrUserClass): void
{
    assertType('TNeverQuery of BuilderOfType\ModelWithNeverQuery (function ModelTemplateClassString\testReceivers(), argument)', $neverQueryClass::findOrFail(1));
    assertType('Illuminate\Database\Eloquent\Collection<int, App\Post>|Illuminate\Database\Eloquent\Collection<int, App\User>', $postOrUserClass::get());
}


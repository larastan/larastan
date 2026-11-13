<?php

namespace UseEloquentBuilderInheritance;

use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use function PHPStan\Testing\assertType;

function test(): void
{
    assertType('UseEloquentBuilderInheritance\BaseBuilder<UseEloquentBuilderInheritance\Post>', Post::query());
    assertType('UseEloquentBuilderInheritance\BaseBuilder<UseEloquentBuilderInheritance\Post>', Post::where('id', 1));
    assertType('UseEloquentBuilderInheritance\BaseBuilder<UseEloquentBuilderInheritance\Comment>', Comment::query());
    assertType('UseEloquentBuilderInheritance\VideoBuilder<UseEloquentBuilderInheritance\Video>', Video::query());
    assertType('Illuminate\Database\Eloquent\Builder<UseEloquentBuilderInheritance\Image>', Image::query());
    assertType('UseEloquentBuilderInheritance\BaseBuilder<UseEloquentBuilderInheritance\Tag>', Tag::query());
    assertType('UseEloquentBuilderInheritance\BaseBuilder<UseEloquentBuilderInheritance\TraitParentChild>', TraitParentChild::query());
    assertType('UseEloquentBuilderInheritance\VideoBuilder<UseEloquentBuilderInheritance\TraitChild>', TraitChild::query());
    assertType('Illuminate\Database\Eloquent\Builder<UseEloquentBuilderInheritance\NestedTraitModel>', NestedTraitModel::query());
    assertType('Illuminate\Database\Eloquent\Builder<UseEloquentBuilderInheritance\InvalidBuilderChild>', InvalidBuilderChild::query());
}

#[UseEloquentBuilder(BaseBuilder::class)]
abstract class BaseModel extends Model
{

}

class Post extends BaseModel
{

}

class Comment extends Post
{

}

#[UseEloquentBuilder(VideoBuilder::class)]
class Video extends BaseModel
{

}

class Image extends BaseModel
{
    /** @return Builder<static> */
    public function newEloquentBuilder($query): Builder
    {
        return new Builder($query);
    }
}

#[UseEloquentBuilder(BaseBuilder::class)]
trait HasBaseBuilder
{

}

#[UseEloquentBuilder(VideoBuilder::class)]
trait HasVideoBuilder
{

}

trait UsesHasBaseBuilder
{
    use HasBaseBuilder;
}

class Tag extends Model
{
    use HasBaseBuilder;
}

abstract class TraitParent extends Model
{
    use HasBaseBuilder;
}

class TraitParentChild extends TraitParent
{

}

class TraitChild extends BaseModel
{
    use HasVideoBuilder;
}

/** Laravel only reads the traits the class uses itself, so the attribute of HasBaseBuilder is skipped. */
class NestedTraitModel extends Model
{
    use UsesHasBaseBuilder;
}

/** Laravel ignores a class that is not an Eloquent builder. */
#[UseEloquentBuilder(Model::class)]
abstract class InvalidBuilderParent extends Model
{

}

class InvalidBuilderChild extends InvalidBuilderParent
{

}

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class BaseBuilder extends Builder
{

}

/**
 * @template TModel of Model
 *
 * @extends Builder<TModel>
 */
class VideoBuilder extends Builder
{

}

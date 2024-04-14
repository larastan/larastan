# Features

All features that are specific to Laravel applications are listed here.

## Laravel 9 Attributes

In order for [Laravel 9 Attributes](https://laravel.com/docs/9.x/eloquent-mutators#accessors-and-mutators) to be recognized as model properties, they must be `protected` methods annotated with the `Attribute` Generic Types.

The first generic type is the getter return type, and the second is the setter argument type.

#### Examples

```php
/** @return Attribute<string[], string[]> */
protected function scopes(): Attribute
{
	return Attribute::make(
		get: fn (?string $value) => is_null($value) ? [] : explode(' ', $value),
		set: function(array $value) {
			$set = array_unique($value);
			sort($set);
			return ['scopes' => implode(' ', $set)];
		}
	);
}
```

```php
/** @return Attribute<bool, never> */
protected function isTrue(): Attribute
{
	return Attribute::make(
		get: fn (?string $value): bool => $value === null,
	);
}
```

## Model Relationships

In order for Larastan to recognize Model relationships:
- the return type must be defined
- the method must be `public`
- the relationship method argument must be a literal string (not a variable)

If the above conditions are not met, then adding the `@return` docblock can help

```php
/** @return BelongsTo<User, $this> */
public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

/** @return HasMany<Post> */
public function posts(): HasMany
{
    return $this->hasMany(Post::class);
}
```

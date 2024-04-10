<?php
declare(strict_types=1);

namespace App;

use App\ValueObjects\Favorites;
use Illuminate\Support\Collection;

/**
 * @extends Collection<array-key, Favorites>
 */
class FavoritesCollection extends Collection
{
}

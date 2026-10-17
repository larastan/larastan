<?php

declare(strict_types=1);

namespace Tests\Type;

use App\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;
use PHPUnit\Framework\TestCase;

use function array_keys;

final class CollectionMultiGroupByRuntimeTest extends TestCase
{
    public function testMultipleLevelsPreserveOriginalKeys(): void
    {
        $rows = new SupportCollection([
            10 => ['department' => 'sales', 'city' => 'Wroclaw'],
            20 => ['department' => 'sales', 'city' => 'Wroclaw'],
        ]);

        $groups = $rows->groupBy(['department', 'city'], preserveKeys: true);

        self::assertInstanceOf(SupportCollection::class, $groups['sales']);
        self::assertInstanceOf(SupportCollection::class, $groups['sales']['Wroclaw']);
        self::assertSame([10, 20], array_keys($groups['sales']['Wroclaw']->all()));
    }

    public function testEmptyEloquentGroupByKeepsAnEloquentCollection(): void
    {
        $groups = (new EloquentCollection())->groupBy(['name', 'region']);

        // Eloquent extends Support, so the extension's outer Support type is
        // intentionally correct even for the empty-collection special case.
        self::assertInstanceOf(EloquentCollection::class, $groups);
        self::assertInstanceOf(SupportCollection::class, $groups);
        self::assertSame([], $groups->all());
    }

    public function testNonEmptyEloquentGroupByKeepsTypedModelLeaves(): void
    {
        $model = new User();
        $model->setRawAttributes(['department' => 'sales', 'region' => 'EU']);

        $groups = (new EloquentCollection([$model]))->groupBy(['department', 'region']);

        // Eloquent converts outer mapped collections to Support collections
        // but keeps Eloquent collections at the leaf containing actual models.
        self::assertInstanceOf(SupportCollection::class, $groups);
        self::assertInstanceOf(SupportCollection::class, $groups['sales']);
        self::assertInstanceOf(EloquentCollection::class, $groups['sales']['EU']);
        self::assertSame([$model], $groups['sales']['EU']->all());
    }

    public function testCallableArrayRepresentsOneGroupingSelector(): void
    {
        $rows = new SupportCollection([
            ['department' => 'sales', 'city' => 'Wroclaw'],
        ]);

        $groups = $rows->groupBy([self::class, 'department']);

        self::assertSame(['sales'], array_keys($groups->all()));
        self::assertSame([['department' => 'sales', 'city' => 'Wroclaw']], $groups['sales']->all());
    }

    /** @param array{department: string, city: string} $row */
    public static function department(array $row): string
    {
        return $row['department'];
    }
}

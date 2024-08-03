<?php

declare(strict_types=1);

namespace Larastan\Larastan\Types;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function app;
use function array_map;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function levenshtein;
use function usort;

class RouteNameStringType extends StringType
{
    public function describe(VerbosityLevel $level): string
    {
        return 'route-name-string';
    }

    public function accepts(Type $type, bool $strictTypes): TrinaryLogic
    {
        return $this->acceptsWithReason($type, $strictTypes)->result;
    }

    public function acceptsWithReason(
        Type $type,
        bool $strictTypes,
    ): AcceptsResult {
        if ($type instanceof self) {
            return AcceptsResult::createYes();
        }

        if ($type instanceof CompoundType) {
            return $type->acceptsWithReason($type, $strictTypes);
        }

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) === 1) {
            $existingRouteNames = $this->resolveExistingRouteNames();
            $routeName          = $constantStrings[0]->getValue();

            if (in_array($routeName, $existingRouteNames)) {
                return AcceptsResult::createYes();
            }

            return new AcceptsResult(
                TrinaryLogic::createNo(),
                [$this->describeWhyNotAccepted($routeName, $existingRouteNames)],
            );
        }

        if ($type->isString()->yes()) {
            return AcceptsResult::createMaybe();
        }

        return AcceptsResult::createNo();
    }

    public function isSuperTypeOf(Type $type): TrinaryLogic
    {
        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) === 1) {
            $routeName          = $constantStrings[0]->getValue();
            $existingRouteNames = $this->resolveExistingRouteNames();

            return TrinaryLogic::createFromBoolean(
                in_array($routeName, $existingRouteNames),
            );
        }

        if ($type instanceof self) {
            return TrinaryLogic::createYes();
        }

        if ($type->isString()->yes()) {
            return TrinaryLogic::createMaybe();
        }

        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }

        return TrinaryLogic::createNo();
    }

    /** @param  mixed[] $properties */
    public static function __set_state(array $properties): Type
    {
        return new self();
    }

    /** @return list<string> */
    private function resolveExistingRouteNames(): array
    {
        $names = [];

        foreach (app('router')->getRoutes()->getRoutes() as $route) {
            $name = $route->getName();
            if ($name === null) {
                continue;
            }

            $names[] = $name;
        }

        return $names;
    }

    /** @param list<string> $existingRouteNames */
    private function describeWhyNotAccepted(
        string $routeName,
        array $existingRouteNames,
    ): string {
        $message = "Route '" . $routeName . "' does not exist.";

        $alternatives = $this->closestRouteNamesTo($routeName, $existingRouteNames);
        if (count($alternatives) > 0) {
            $quoted = array_map(static fn (string $name) => "'" . $name . "'", $alternatives);

            if (count($alternatives) === 1) {
                $message .= ' Did you mean ' . $quoted[0] . '?';
            } else {
                $list     = implode(', ', array_slice($quoted, 0, -1));
                $last     = $quoted[count($quoted) - 1];
                $message .= ' Did you mean ' . $list . ' or ' . $last . '?';
            }
        }

        return $message;
    }

    /**
     * Tries to find similarly named routes to the given one using
     * the {@link levenshtein()}-distance.
     *
     * @param  string       $query              A route name that probably does not exist, but we search a similarly named route for
     * @param  list<string> $existingRouteNames All known route names
     * @param  int          $threshold          Max. acceptable edit distance. Inversely proportional to the number of returned results.
     * @param  int          $maxResults         Max. number of returned results
     *
     * @return list<string>
     */
    private function closestRouteNamesTo(
        string $query,
        array $existingRouteNames,
        int $threshold = 3,
        int $maxResults = 3,
    ): array {
        $withDistance = array_map(static function (string $existingRoute) use ($query) {
            return [$existingRoute, levenshtein($query, $existingRoute)];
        }, $existingRouteNames);

        // This sorts the name-distance pairs in ascending order. Therefore, the
        // most similar names are at the top
        usort(
            $withDistance,
            /**
             * @param  array{0: string, 1: int}  $a
             * @param  array{0: string, 1: int}  $b
             */
            static fn (array $a, array $b): int => $a[1] - $b[1],
        );

        $results = [];
        foreach ($withDistance as [$route, $distance]) {
            if (count($results) === $maxResults) {
                break;
            }

            if ($distance > $threshold) {
                break;
            }

            $results[] = $route;
        }

        return $results;
    }
}

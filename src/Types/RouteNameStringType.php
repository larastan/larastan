<?php

namespace NunoMaduro\Larastan\Types;

use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;

use function array_map;
use function array_slice;
use function count;
use function implode;
use function in_array;
use function levenshtein;
use function usort;

class RouteNameStringType extends StringType
{
    /**
     * @param  list<string>  $existingRouteNames
     */
    public function __construct(private array $existingRouteNames)
    {
        parent::__construct();
    }

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
        bool $strictTypes
    ): AcceptsResult {
        if ($type instanceof self) {
            return AcceptsResult::createYes();
        }

        if ($type instanceof CompoundType) {
            return $type->acceptsWithReason($type, $strictTypes);
        }

        $constantStrings = $type->getConstantStrings();
        if (count($constantStrings) === 1) {
            $routeName = $constantStrings[0]->getValue();

            if ($this->routeExists($routeName)) {
                return AcceptsResult::createYes();
            }

            // @phpstan-ignore-next-line Phpstan tells me, that this function is not covered by their BC promise, even though the class clearly has the @api tag in its doc-comment.
            return new AcceptsResult(
                TrinaryLogic::createNo(),
                [$this->describeWhyNotAccepted($routeName)],
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
            return TrinaryLogic::createFromBoolean($this->routeExists($constantStrings[0]->getValue()));
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

    /**
     * @param  mixed[]  $properties
     * @return Type
     */
    public static function __set_state(array $properties): Type
    {
        return new self($properties['existingRouteNames']);
    }

    private function routeExists(string $name): bool
    {
        return in_array($name, $this->existingRouteNames);
    }

    private function describeWhyNotAccepted(string $routeName): string
    {
        $message = "Route '$routeName' does not exist.";

        $alternatives = $this->closestRouteNamesTo($routeName);
        if (count($alternatives) > 0) {
            $quoted = array_map(fn (string $name) => "'$name'", $alternatives);

            if (count($alternatives) === 1) {
                $message .= ' Did you mean '.$quoted[0].'?';
            } else {
                $list = implode(', ', array_slice($quoted, 0, -1));
                $last = $quoted[count($quoted) - 1];
                $message .= " Did you mean $list or $last?";
            }
        }

        return $message;
    }

    /**
     * Tries to find similarly named routes to the given one using
     * the {@link levenshtein()}-distance.
     *
     * @param  string  $query  A route name that probably does not exist, but we search a similarly named route for
     * @param  int  $threshold  Max. acceptable edit distance. Inversely proportional to the number of returned results.
     * @param  int  $maxResults  Max. number of returned results
     * @return list<string>
     */
    private function closestRouteNamesTo(
        string $query,
        int $threshold = 3,
        int $maxResults = 3,
    ): array {
        $withDistance = array_map(function (string $existingRoute) use ($query) {
            return [$existingRoute, levenshtein($query, $existingRoute)];
        }, $this->existingRouteNames);

        // This sorts the name-distance pairs in ascending order. Therefore, the
        // most similar names are at the top
        usort(
            $withDistance,
            /**
             * @param  array{0: string, 1: int}  $a
             * @param  array{0: string, 1: int}  $b
             */
            fn (array $a, array $b): int => $a[1] - $b[1]
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

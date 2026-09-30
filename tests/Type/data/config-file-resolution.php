<?php

namespace ConfigFileResolution;

use function PHPStan\Testing\assertType;

function test(): void
{
    // A key only matches a file whose whole name equals it, never a suffix of it
    assertType("'from app.php'", config('app.v'));
    assertType("'from webapp.php'", config('webapp.v'));
    assertType("'from rcdup.php'", config('rcdup.v'));
    assertType("'from xrcdup.php'", config('xrcdup.v'));
    assertType('mixed', config('rcapp.v'));

    // Files in subdirectories are keyed by their relative path, like Laravel does
    assertType("'from nested/rcnest.php'", config('nested.rcnest.v'));
    assertType("'from nested/deep/leaf.php'", config('nested.deep.leaf.v'));
    assertType('int', config('nested.typed.v'));
    assertType('mixed', config('rcnest.v'));
    assertType('mixed', config('leaf.v'));

    // Laravel sets nested files over nested.php, so the deepest file wins
    assertType("'from nested.php'", config('nested.v'));
    assertType("'from nested.php'", config('nested.deep.v'));
    assertType("array{v: 'from nested/rcnest.php'}", config('nested.rcnest'));

    // Values that Laravel merges from several files are not inferred
    assertType('mixed', config('nested'));
    assertType('mixed', config('nested.deep'));

    // Config directories can be glob patterns
    assertType("'from modules/a/config/rcmoda.php'", config('rcmoda.v'));
    assertType("'from modules/b/config/rcmodb.php'", config('rcmodb.v'));

    // A directory that exists is used as is, even if its path has glob characters
    assertType("'from literal[1]/config/rcliteral.php'", config('rcliteral.v'));
}

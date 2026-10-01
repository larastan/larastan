<?php

declare(strict_types=1);

namespace Larastan\Larastan\Support\Validation;

/**
 * The stages at which request data can be looked at.
 *
 * @internal
 */
enum DataView
{
    /** The input of a request that passed validation. Excluded fields are present but were not validated. */
    case Input;

    /** Input that was copied into the validated data as a whole, after exclusion removed the excluded fields. */
    case Copied;

    /** The validated data, which holds only the fields that have rules. */
    case Validated;
}

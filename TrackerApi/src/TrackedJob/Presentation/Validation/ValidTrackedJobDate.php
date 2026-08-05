<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Validation;

use Symfony\Component\Validator\Constraint;

/**
 * Validates date values accepted by tracked job HTTP payloads.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class ValidTrackedJobDate extends Constraint
{
    public string $message = 'This value should be a valid date.';
}
<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Validation;

use App\TrackedJob\Application\Date\TrackedJobDateParser;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Delegates tracked job date validation to the application date parser.
 */
final class ValidTrackedJobDateValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidTrackedJobDate) {
            return;
        }

        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value) || !TrackedJobDateParser::isValid($value)) {
            $this->context->buildViolation($constraint->message)->addViolation();
        }
    }
}
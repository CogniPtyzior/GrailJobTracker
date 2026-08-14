<?php

declare(strict_types=1);

/*
 * API Platform input DTO for tracked job creation.
 * It exists separately from update input so future create-only constraints can be added explicitly.
 */

namespace App\TrackedJob\Api\Input;

final class CreateTrackedJobInput extends AbstractTrackedJobInput
{
}
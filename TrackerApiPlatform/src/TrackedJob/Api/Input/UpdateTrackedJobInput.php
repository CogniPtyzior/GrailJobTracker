<?php

declare(strict_types=1);

/*
 * API Platform input DTO for tracked job updates.
 * It mirrors the create payload today to preserve the legacy frontend PUT contract.
 */

namespace App\TrackedJob\Api\Input;

final class UpdateTrackedJobInput extends AbstractTrackedJobInput
{
}
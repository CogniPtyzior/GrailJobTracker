<?php

declare(strict_types=1);

/*
 * Application exception for state conflicts.
 * Use cases raise it when a command would violate uniqueness or another persisted application constraint.
 */

namespace App\Shared\Application\Exception;

final class ApplicationConflict extends ApplicationException
{
}

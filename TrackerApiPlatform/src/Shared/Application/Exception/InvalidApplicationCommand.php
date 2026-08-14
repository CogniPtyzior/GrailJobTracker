<?php

declare(strict_types=1);

/*
 * Application exception for invalid commands that passed transport-level parsing.
 * Use cases raise it for application rules that are not pure domain invariants.
 */

namespace App\Shared\Application\Exception;

final class InvalidApplicationCommand extends ApplicationException
{
}

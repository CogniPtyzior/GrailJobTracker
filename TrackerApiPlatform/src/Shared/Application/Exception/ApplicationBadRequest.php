<?php

declare(strict_types=1);

/*
 * Application exception for bad requests that are not field-level validation errors.
 * Use cases raise it when an operation is well-formed but violates a request-level application guard.
 */

namespace App\Shared\Application\Exception;

final class ApplicationBadRequest extends ApplicationException
{
}

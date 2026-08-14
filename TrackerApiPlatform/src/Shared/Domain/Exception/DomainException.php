<?php

declare(strict_types=1);

/*
 * Base exception for domain invariant violations.
 * It extends InvalidArgumentException to preserve value-object failure semantics from the legacy backend.
 */

namespace App\Shared\Domain\Exception;

use InvalidArgumentException;

abstract class DomainException extends InvalidArgumentException implements DomainExceptionInterface
{
}

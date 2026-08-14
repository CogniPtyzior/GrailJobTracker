<?php

declare(strict_types=1);

/*
 * Base exception for application-layer failures.
 * API Platform maps this base class to a client error while specialized subclasses refine the status code.
 */

namespace App\Shared\Application\Exception;

use RuntimeException;

abstract class ApplicationException extends RuntimeException implements ApplicationExceptionInterface
{
}

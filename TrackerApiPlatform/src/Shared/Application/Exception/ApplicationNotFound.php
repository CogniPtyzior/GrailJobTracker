<?php

declare(strict_types=1);

/*
 * Application exception for missing resources.
 * Use cases raise it when the requested aggregate or read model does not exist in the allowed scope.
 */

namespace App\Shared\Application\Exception;

final class ApplicationNotFound extends ApplicationException
{
}

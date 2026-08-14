<?php

declare(strict_types=1);

/*
 * Marker contract for domain exceptions.
 * Domain code raises these exceptions for business invariant violations without knowing HTTP or API Platform.
 */

namespace App\Shared\Domain\Exception;

interface DomainExceptionInterface
{
}

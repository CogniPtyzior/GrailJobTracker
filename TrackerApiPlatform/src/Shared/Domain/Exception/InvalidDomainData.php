<?php

declare(strict_types=1);

/*
 * Generic domain exception for invalid business data.
 * Value objects use it when a raw value cannot satisfy a domain invariant.
 */

namespace App\Shared\Domain\Exception;

final class InvalidDomainData extends DomainException
{
}

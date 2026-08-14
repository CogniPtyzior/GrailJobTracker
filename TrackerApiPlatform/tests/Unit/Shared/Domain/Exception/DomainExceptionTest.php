<?php

declare(strict_types=1);

/*
 * Unit tests for the domain exception foundation.
 * They ensure business exceptions remain transport-agnostic while preserving InvalidArgumentException semantics.
 */

use App\Shared\Domain\Exception\DomainException;
use App\Shared\Domain\Exception\DomainExceptionInterface;
use App\Shared\Domain\Exception\InvalidDomainData;

it('marks invalid domain data as a domain exception', function (): void {
    $exception = new InvalidDomainData('Invalid value.');

    expect($exception)
        ->toBeInstanceOf(DomainException::class)
        ->toBeInstanceOf(DomainExceptionInterface::class)
        ->toBeInstanceOf(InvalidArgumentException::class)
        ->and($exception->getMessage())
        ->toBe('Invalid value.');
});

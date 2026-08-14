<?php

declare(strict_types=1);

/*
 * Unit tests for the application exception foundation.
 * They keep use-case failures independent from HTTP while allowing API Platform status-code mapping.
 */

use App\Shared\Application\Exception\ApplicationConflict;
use App\Shared\Application\Exception\ApplicationException;
use App\Shared\Application\Exception\ApplicationExceptionInterface;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Application\Exception\InvalidApplicationCommand;

it('marks missing resources as application exceptions', function (): void {
    expect(new ApplicationNotFound('Missing resource.'))
        ->toBeInstanceOf(ApplicationException::class)
        ->toBeInstanceOf(ApplicationExceptionInterface::class)
        ->toBeInstanceOf(RuntimeException::class);
});

it('marks conflicts as application exceptions', function (): void {
    expect(new ApplicationConflict('Duplicate resource.'))
        ->toBeInstanceOf(ApplicationException::class)
        ->toBeInstanceOf(ApplicationExceptionInterface::class);
});

it('marks invalid commands as application exceptions', function (): void {
    expect(new InvalidApplicationCommand('Invalid command.'))
        ->toBeInstanceOf(ApplicationException::class)
        ->toBeInstanceOf(ApplicationExceptionInterface::class);
});

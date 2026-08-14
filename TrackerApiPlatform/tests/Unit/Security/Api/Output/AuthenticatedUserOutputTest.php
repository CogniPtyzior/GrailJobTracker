<?php

declare(strict_types=1);

/*
 * Unit tests for the authenticated user API output mapping.
 * They protect the frontend response shape independently from Symfony HTTP handling.
 */

use App\Security\Api\Output\AuthenticatedUserOutput;
use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

it('maps a domain user to the frontend-compatible authentication output', function (): void {
    $createdAt = new DateTimeImmutable('2026-01-02 03:04:05', new DateTimeZone('UTC'));
    $lastLoginAt = new DateTimeImmutable('2026-01-03 04:05:06', new DateTimeZone('UTC'));
    $user = User::reconstitute(
        UserId::fromString('018f05c7-921d-7298-94dd-57d9be9d4904'),
        EmailAddress::fromString('Course.Author@example.com'),
        PersonName::fromNullable('Ada'),
        PersonName::fromNullable('Lovelace'),
        true,
        UserRoles::admin(),
        'hash',
        $createdAt,
        $lastLoginAt,
    );

    $output = AuthenticatedUserOutput::fromDomain($user);

    expect($output->user->id)->toBe('018f05c7-921d-7298-94dd-57d9be9d4904')
        ->and($output->user->email)->toBe('Course.Author@example.com')
        ->and($output->user->firstName)->toBe('Ada')
        ->and($output->user->lastName)->toBe('Lovelace')
        ->and($output->user->roles)->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($output->user->isActive)->toBeTrue()
        ->and($output->user->createdAt)->toBe('2026-01-02T03:04:05+00:00')
        ->and($output->user->lastLoginAt)->toBe('2026-01-03T04:05:06+00:00');
});

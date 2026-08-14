<?php

declare(strict_types=1);

/*
 * Unit tests for the user Doctrine mapper.
 * They lock the conversion between the domain aggregate and the preserved trackers.users schema.
 */

use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\Security\Infrastructure\Doctrine\Mapper\UserRecordMapper;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Symfony\Component\Uid\Uuid;

it('maps Doctrine records to domain users', function (): void {
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');
    $lastLoginAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');
    $record = new UserRecord();
    $record->setId(Uuid::fromString('018f6d6f-0000-7000-8000-000000000001'));
    $record->setEmail('John@example.com');
    $record->setNormalizedEmail('john@example.com');
    $record->setFirstName('John');
    $record->setLastName('Doe');
    $record->setIsActive(false);
    $record->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
    $record->setPasswordHash('persisted-hash');
    $record->setCreatedAt($createdAt);
    $record->setLastLoginAt($lastLoginAt);

    $user = (new UserRecordMapper())->toDomain($record);

    expect($user->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000001')
        ->and($user->getEmail())->toBe('John@example.com')
        ->and($user->getNormalizedEmail())->toBe('john@example.com')
        ->and($user->firstName()?->value())->toBe('John')
        ->and($user->lastName()?->value())->toBe('Doe')
        ->and($user->isActive())->toBeFalse()
        ->and($user->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($user->getPassword())->toBe('persisted-hash')
        ->and($user->getCreatedAt())->toBe($createdAt)
        ->and($user->getLastLoginAt())->toBe($lastLoginAt);
});

it('updates Doctrine records from domain users', function (): void {
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');
    $lastLoginAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');
    $user = User::reconstitute(
        UserId::fromString('018f6d6f-0000-7000-8000-000000000002'),
        EmailAddress::fromString('Jane@example.com'),
        PersonName::fromNullable(' Jane '),
        PersonName::fromNullable(' Doe '),
        true,
        UserRoles::admin(),
        'hashed-password',
        $createdAt,
        $lastLoginAt,
    );
    $record = new UserRecord();

    (new UserRecordMapper())->updateRecord($user, $record);

    expect($record->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000002')
        ->and($record->getEmail())->toBe('Jane@example.com')
        ->and($record->getNormalizedEmail())->toBe('jane@example.com')
        ->and($record->getFirstName())->toBe('Jane')
        ->and($record->getLastName())->toBe('Doe')
        ->and($record->isActive())->toBeTrue()
        ->and($record->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($record->getPasswordHash())->toBe('hashed-password')
        ->and($record->getCreatedAt())->toBe($createdAt)
        ->and($record->getLastLoginAt())->toBe($lastLoginAt);
});

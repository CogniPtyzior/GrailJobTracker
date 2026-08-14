<?php

declare(strict_types=1);

/*
 * Unit tests for the user aggregate.
 * They preserve legacy user behavior before authentication and admin APIs are migrated.
 */

use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

it('initializes identity defaults', function (): void {
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');
    $user = new User(EmailAddress::fromString('John@example.com'), $createdAt);

    expect($user->getId())->toBeInstanceOf(UserId::class)
        ->and($user->getEmail())->toBe('John@example.com')
        ->and($user->getNormalizedEmail())->toBe('john@example.com')
        ->and($user->isActive())->toBeTrue()
        ->and($user->getRoles())->toBe(['ROLE_USER'])
        ->and($user->getCreatedAt())->toBe($createdAt)
        ->and($user->getLastLoginAt())->toBeNull();
});

it('owns role assignments', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    $user->grantAdmin();
    expect($user->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER']);

    $user->assignRegularUser();
    expect($user->getRoles())->toBe(['ROLE_USER']);
});

it('switches admin role from a boolean command', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    $user->updateAdminRole(true);
    expect($user->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER']);

    $user->updateAdminRole(false);
    expect($user->getRoles())->toBe(['ROLE_USER']);
});

it('updates optional profile names', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    $user->updateProfile(PersonName::fromNullable('  John  '), PersonName::fromNullable('  Doe  '));
    expect($user->firstName()?->value())->toBe('John')
        ->and($user->lastName()?->value())->toBe('Doe');

    $user->updateProfile(null, null);
    expect($user->firstName())->toBeNull()
        ->and($user->lastName())->toBeNull();
});

it('owns activation state', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    $user->deactivate();
    expect($user->isActive())->toBeFalse();

    $user->activate();
    expect($user->isActive())->toBeTrue();
});

it('detects bootstrap admin through normalized email comparison', function (): void {
    $user = new User(EmailAddress::fromString('John@example.com'));

    expect($user->isBootstrapAdmin('  JOHN@example.com  '))->toBeTrue()
        ->and($user->isBootstrapAdmin('other@example.com'))->toBeFalse();
});

it('stores password hashes and login timestamps', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));
    $loggedAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');

    $user->setPasswordHash('hashed-password');
    $user->markLoggedIn($loggedAt);

    expect($user->getPassword())->toBe('hashed-password')
        ->and($user->getLastLoginAt())->toBe($loggedAt);
});

it('changes email and normalized email together', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    $user->changeEmail(EmailAddress::fromString('Jane@example.com'));

    expect($user->getEmail())->toBe('Jane@example.com')
        ->and($user->getNormalizedEmail())->toBe('jane@example.com');
});

it('reconstitutes persisted state', function (): void {
    $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
    $createdAt = new DateTimeImmutable('2026-04-01T10:00:00+00:00');
    $lastLoginAt = new DateTimeImmutable('2026-04-20T12:30:00+00:00');

    $user = User::reconstitute(
        $id,
        EmailAddress::fromString('John@example.com'),
        PersonName::fromNullable('  John  '),
        PersonName::fromNullable('  Doe  '),
        false,
        UserRoles::fromArray([' ROLE_ADMIN ', '', 'ROLE_ADMIN', 'ROLE_USER']),
        'persisted-hash',
        $createdAt,
        $lastLoginAt,
    );

    expect($user->getId())->toBe($id)
        ->and($user->getEmail())->toBe('John@example.com')
        ->and($user->firstName()?->value())->toBe('John')
        ->and($user->lastName()?->value())->toBe('Doe')
        ->and($user->isActive())->toBeFalse()
        ->and($user->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($user->getPassword())->toBe('persisted-hash')
        ->and($user->getCreatedAt())->toBe($createdAt)
        ->and($user->getLastLoginAt())->toBe($lastLoginAt);
});

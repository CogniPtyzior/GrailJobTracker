<?php

namespace App\Tests\Unit\Security\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;
use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserTest extends TestCase
{
    public function testConstructorInitializesIdentityDefaults(): void
    {
        $user = new User(EmailAddress::fromString('John@example.com'));

        self::assertNotNull($user->getId());
        self::assertSame('John@example.com', $user->getEmail());
        self::assertSame('john@example.com', $user->getNormalizedEmail());
        self::assertTrue($user->isActive());
        self::assertSame(['ROLE_USER'], $user->getRoles());
        self::assertNotNull($user->getCreatedAt());
        self::assertNull($user->getLastLoginAt());
    }

    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));
        $user->grantAdmin();

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testRoleMethodsOwnRoleAssignments(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->grantAdmin();
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->assignRegularUser();
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUpdateAdminRoleSwitchesBetweenAdminAndRegularUser(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->updateAdminRole(true);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->updateAdminRole(false);
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUpdateProfileTrimsNamesAndAllowsNull(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->updateProfile(PersonName::fromNullable('  John  '), PersonName::fromNullable('  Doe  '));
        self::assertSame('John', $user->firstName()?->value());
        self::assertSame('Doe', $user->lastName()?->value());

        $user->updateProfile(null, null);
        self::assertNull($user->firstName()?->value());
        self::assertNull($user->lastName()?->value());
    }

    public function testUpdateProfileConvertsBlankNamesToNull(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->updateProfile(PersonName::fromNullable('   '), PersonName::fromNullable('   '));

        self::assertNull($user->firstName()?->value());
        self::assertNull($user->lastName()?->value());
    }

    public function testActivationMethodsOwnTheActiveFlag(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->deactivate();
        self::assertFalse($user->isActive());

        $user->activate();
        self::assertTrue($user->isActive());
    }

    public function testIsBootstrapAdminUsesNormalizedComparison(): void
    {
        $user = new User(EmailAddress::fromString('John@example.com'));

        self::assertTrue($user->isBootstrapAdmin('  JOHN@example.com  '));
        self::assertFalse($user->isBootstrapAdmin('other@example.com'));
    }

    public function testPasswordHashIsReturnedForSymfonySecurity(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));

        $user->setPasswordHash('hashed-password');

        self::assertSame('hashed-password', $user->getPassword());
    }

    public function testMarkLoggedInStoresTimestamp(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));
        $loggedAt = FixedDates::april20();
        $user->markLoggedIn($loggedAt);

        self::assertSame($loggedAt, $user->getLastLoginAt());
    }

    public function testChangeEmailUpdatesBothEmailAndNormalizedEmail(): void
    {
        $user = new User(EmailAddress::fromString('john@example.com'));
        $user->changeEmail(EmailAddress::fromString('Jane@example.com'));

        self::assertSame('Jane@example.com', $user->getEmail());
        self::assertSame('jane@example.com', $user->getNormalizedEmail());
        self::assertSame('jane@example.com', $user->getUserIdentifier());
    }

    public function testIsEqualToComparesSecurityRelevantFields(): void
    {
        $user = UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->build();
        $sameUser = UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->build();
        $differentPassword = UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('other')->build();
        $inactiveUser = UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->inactive()->build();
        $adminUser = UserBuilder::aUser()
            ->withEmail('john@example.com')
            ->withPasswordHash('hash')
            ->withRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->build();

        self::assertTrue($user->isEqualTo($sameUser));
        self::assertFalse($user->isEqualTo($differentPassword));
        self::assertFalse($user->isEqualTo($inactiveUser));
        self::assertFalse($user->isEqualTo($adminUser));
    }

    public function testIsEqualToRejectsDifferentUserImplementation(): void
    {
        $user = UserBuilder::aUser()->build();
        $otherImplementation = new class implements UserInterface {
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'john@example.com';
            }
        };

        self::assertFalse($user->isEqualTo($otherImplementation));
    }

    public function testReconstituteRestoresPersistedStateAndCleansRoles(): void
    {
        $id = UserId::fromString('018f6d6f-0000-7000-8000-000000000001');
        $createdAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');
        $lastLoginAt = new \DateTimeImmutable('2026-04-20T12:30:00+00:00');

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

        self::assertSame($id, $user->getId());
        self::assertSame('John@example.com', $user->getEmail());
        self::assertSame('john@example.com', $user->getNormalizedEmail());
        self::assertSame('John', $user->firstName()?->value());
        self::assertSame('Doe', $user->lastName()?->value());
        self::assertFalse($user->isActive());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame('persisted-hash', $user->getPassword());
        self::assertSame($createdAt, $user->getCreatedAt());
        self::assertSame($lastLoginAt, $user->getLastLoginAt());
    }

    public function testReconstituteDefaultsToRoleUserWhenPersistedRolesAreEmpty(): void
    {
        $user = User::reconstitute(
            UserId::fromString('018f6d6f-0000-7000-8000-000000000002'),
            EmailAddress::fromString('john@example.com'),
            null,
            null,
            true,
            UserRoles::fromArray(['', '   ']),
            'persisted-hash',
            new \DateTimeImmutable('2026-04-01T10:00:00+00:00'),
            null,
        );

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}


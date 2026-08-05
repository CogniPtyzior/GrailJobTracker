<?php

namespace App\Tests\Unit\Security\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserTest extends TestCase
{
    public function testConstructorInitializesIdentityDefaults(): void
    {
        $user = new User('John@example.com', 'john@example.com');

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
        $user = new User('john@example.com', 'john@example.com');
        $user->grantAdmin();

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testRoleMethodsOwnRoleAssignments(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->grantAdmin();
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->assignRegularUser();
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUpdateAdminRoleSwitchesBetweenAdminAndRegularUser(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->updateAdminRole(true);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->updateAdminRole(false);
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testUpdateProfileTrimsNamesAndAllowsNull(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->updateProfile('  John  ', '  Doe  ');
        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());

        $user->updateProfile(null, null);
        self::assertNull($user->getFirstName());
        self::assertNull($user->getLastName());
    }

    public function testUpdateProfilePreservesBlankStringsAsTrimmedEmptyValues(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->updateProfile('   ', '   ');

        self::assertSame('', $user->getFirstName());
        self::assertSame('', $user->getLastName());
    }

    public function testActivationMethodsOwnTheActiveFlag(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->deactivate();
        self::assertFalse($user->isActive());

        $user->activate();
        self::assertTrue($user->isActive());
    }

    public function testIsBootstrapAdminUsesNormalizedComparison(): void
    {
        $user = new User('John@example.com', 'john@example.com');

        self::assertTrue($user->isBootstrapAdmin('  JOHN@example.com  '));
        self::assertFalse($user->isBootstrapAdmin('other@example.com'));
    }

    public function testPasswordHashIsReturnedForSymfonySecurity(): void
    {
        $user = new User('john@example.com', 'john@example.com');

        $user->setPasswordHash('hashed-password');

        self::assertSame('hashed-password', $user->getPassword());
    }

    public function testMarkLoggedInStoresTimestamp(): void
    {
        $user = new User('john@example.com', 'john@example.com');
        $loggedAt = FixedDates::april20();
        $user->markLoggedIn($loggedAt);

        self::assertSame($loggedAt, $user->getLastLoginAt());
    }

    public function testChangeEmailUpdatesBothEmailAndNormalizedEmail(): void
    {
        $user = new User('john@example.com', 'john@example.com');
        $user->changeEmail('Jane@example.com', 'jane@example.com');

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
}
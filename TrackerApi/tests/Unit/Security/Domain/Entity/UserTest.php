<?php

namespace App\Tests\Unit\Security\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testGetRolesAlwaysContainsRoleUser(): void
    {
        $user = new User('john@example.com', 'john@example.com');
        $user->setRoles(['ROLE_ADMIN']);

        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
    }

    public function testSetRolesDeduplicatesAndFallsBackToRoleUser(): void
    {
        $user = new User('john@example.com', 'john@example.com');
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN', ' ']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());

        $user->setRoles([]);
        self::assertSame(['ROLE_USER'], $user->getRoles());
    }

    public function testNamesAreTrimmed(): void
    {
        $user = new User('john@example.com', 'john@example.com');
        $user->setFirstName('  John  ');
        $user->setLastName('  Doe  ');

        self::assertSame('John', $user->getFirstName());
        self::assertSame('Doe', $user->getLastName());
    }

    public function testIsBootstrapAdminUsesNormalizedComparison(): void
    {
        $user = new User('John@example.com', 'john@example.com');

        self::assertTrue($user->isBootstrapAdmin('  JOHN@example.com  '));
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
    }
}
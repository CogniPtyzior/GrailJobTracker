<?php

namespace App\Tests\Unit\Security\Infrastructure;

use App\Security\Infrastructure\Security\SecurityUser;
use App\Tests\Support\Builder\UserBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

final class SecurityUserTest extends TestCase
{
    public function testExposesSecurityDataFromDomainUser(): void
    {
        $user = UserBuilder::aUser()
            ->withEmail('John@example.com')
            ->withPasswordHash('hash')
            ->withRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->build();

        $securityUser = new SecurityUser($user);

        self::assertSame($user, $securityUser->domainUser());
        self::assertSame('john@example.com', $securityUser->getUserIdentifier());
        self::assertSame('hash', $securityUser->getPassword());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $securityUser->getRoles());
    }

    public function testIsEqualToComparesSecurityRelevantFields(): void
    {
        $user = new SecurityUser(UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->build());
        $sameUser = new SecurityUser(UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->build());
        $differentPassword = new SecurityUser(UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('other')->build());
        $inactiveUser = new SecurityUser(UserBuilder::aUser()->withEmail('john@example.com')->withPasswordHash('hash')->inactive()->build());
        $adminUser = new SecurityUser(UserBuilder::aUser()
            ->withEmail('john@example.com')
            ->withPasswordHash('hash')
            ->withRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->build());

        self::assertTrue($user->isEqualTo($sameUser));
        self::assertFalse($user->isEqualTo($differentPassword));
        self::assertFalse($user->isEqualTo($inactiveUser));
        self::assertFalse($user->isEqualTo($adminUser));
    }

    public function testIsEqualToRejectsDifferentUserImplementation(): void
    {
        $securityUser = new SecurityUser(UserBuilder::aUser()->build());
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

        self::assertFalse($securityUser->isEqualTo($otherImplementation));
    }
}
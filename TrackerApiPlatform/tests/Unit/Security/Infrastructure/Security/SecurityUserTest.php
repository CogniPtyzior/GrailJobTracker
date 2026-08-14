<?php

declare(strict_types=1);

/*
 * Unit tests for the Symfony security user wrapper.
 * They keep Symfony contracts isolated from the domain user aggregate.
 */

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

it('exposes Symfony security data from the domain user', function (): void {
    $user = new User(EmailAddress::fromString('John@example.com'));
    $user->grantAdmin();
    $user->setPasswordHash('hashed-password');
    $securityUser = new SecurityUser($user);

    expect($securityUser)
        ->toBeInstanceOf(UserInterface::class)
        ->toBeInstanceOf(PasswordAuthenticatedUserInterface::class)
        ->and($securityUser->getUserIdentifier())->toBe('john@example.com')
        ->and($securityUser->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($securityUser->getPassword())->toBe('hashed-password')
        ->and($securityUser->domainUser())->toBe($user);
});

it('compares relevant security state', function (): void {
    $left = new User(EmailAddress::fromString('john@example.com'));
    $left->setPasswordHash('same-hash');
    $right = new User(EmailAddress::fromString('JOHN@example.com'));
    $right->setPasswordHash('same-hash');

    expect((new SecurityUser($left))->isEqualTo(new SecurityUser($right)))->toBeTrue();

    $right->deactivate();

    expect((new SecurityUser($left))->isEqualTo(new SecurityUser($right)))->toBeFalse();
});

<?php

declare(strict_types=1);

/*
 * Unit tests for the domain-backed Symfony user provider.
 * They prove authentication loads, refreshes and upgrades users through the repository port.
 */

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\DomainUserProvider;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryTransactionManager;
use App\Tests\Support\Fake\InMemoryUserRepository;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

it('loads users by normalized email', function (): void {
    $repository = new InMemoryUserRepository();
    $repository->add(new User(EmailAddress::fromString('John@example.com')));

    $securityUser = (new DomainUserProvider($repository, new InMemoryTransactionManager()))->loadUserByIdentifier(' john@example.com ');

    expect($securityUser)->toBeInstanceOf(SecurityUser::class)
        ->and($securityUser->getUserIdentifier())->toBe('john@example.com');
});

it('throws when a user cannot be loaded', function (): void {
    (new DomainUserProvider(new InMemoryUserRepository(), new InMemoryTransactionManager()))->loadUserByIdentifier('missing@example.com');
})->throws(UserNotFoundException::class);

it('refreshes users from the repository', function (): void {
    $repository = new InMemoryUserRepository();
    $user = new User(EmailAddress::fromString('john@example.com'));
    $repository->add($user);

    $refreshed = (new DomainUserProvider($repository, new InMemoryTransactionManager()))->refreshUser(new SecurityUser($user));

    expect($refreshed)->toBeInstanceOf(SecurityUser::class)
        ->and($refreshed->domainUser())->toBe($user);
});

it('upgrades passwords through the repository', function (): void {
    $repository = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $user = new User(EmailAddress::fromString('john@example.com'));
    $repository->add($user);

    (new DomainUserProvider($repository, $transactionManager))->upgradePassword(new SecurityUser($user), 'new-hash');

    expect($user->getPassword())->toBe('new-hash')
        ->and($repository->saveCalls)->toBe(1)
        ->and($transactionManager->transactionCalls)->toBe(1);
});

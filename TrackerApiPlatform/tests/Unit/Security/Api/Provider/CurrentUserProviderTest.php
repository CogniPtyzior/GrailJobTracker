<?php

declare(strict_types=1);

/*
 * Unit tests for the API Platform current user provider.
 * They verify the authenticated and anonymous paths without depending on the database.
 */

use ApiPlatform\Metadata\Get;
use App\Security\Api\Provider\CurrentUserProvider;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;

it('returns the authenticated domain user as API output', function (): void {
    $domainUser = new User(EmailAddress::fromString('student@example.com'));
    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($domainUser), 'main', ['ROLE_USER']));

    $output = (new CurrentUserProvider($tokenStorage))->provide(new Get());

    expect($output->user->email)->toBe('student@example.com')
        ->and($output->user->roles)->toBe(['ROLE_USER']);
});

it('rejects anonymous access before producing API output', function (): void {
    $provider = new CurrentUserProvider(new TokenStorage());

    $provider->provide(new Get());
})->throws(AuthenticationCredentialsNotFoundException::class, 'Authentication required.');

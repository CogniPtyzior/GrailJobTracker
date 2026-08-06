<?php

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class DomainUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByEmail(EmailAddress::fromString($identifier));

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User "%s" was not found.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $refreshedUser = $this->userRepository->getById($user->getId());

        if (!$refreshedUser instanceof User) {
            throw new UserNotFoundException('User no longer exists.');
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $user->setPasswordHash($newHashedPassword);
        $this->userRepository->save($user);
        $this->userRepository->flush();
    }
}

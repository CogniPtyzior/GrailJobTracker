<?php

declare(strict_types=1);

/*
 * Symfony user provider backed by the domain user repository.
 * It converts persisted domain users into SecurityUser objects for the authentication layer.
 */

namespace App\Security\Infrastructure\Security;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<SecurityUser>
 */
final readonly class DomainUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->findOneByEmail(EmailAddress::fromString($identifier));

        if (!$user instanceof User) {
            throw new UserNotFoundException(sprintf('User  %s was not found.', $identifier));
        }

        return new SecurityUser($user);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class %s.', $user::class));
        }

        $refreshedUser = $this->userRepository->getById($user->domainUser()->getId());

        if (!$refreshedUser instanceof User) {
            throw new UserNotFoundException('User no longer exists.');
        }

        return new SecurityUser($refreshedUser);
    }

    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class || is_subclass_of($class, SecurityUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException(sprintf('Unsupported user class %s.', $user::class));
        }

        $this->transactionManager->transactional(function () use ($user, $newHashedPassword): void {
            $domainUser = $user->domainUser();
            $domainUser->setPasswordHash($newHashedPassword);
            $this->userRepository->save($domainUser);
        });
    }
}

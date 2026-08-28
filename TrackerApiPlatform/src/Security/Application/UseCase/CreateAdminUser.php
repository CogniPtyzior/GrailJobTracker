<?php

declare(strict_types=1);

/*
 * Application use case for creating users from the admin API.
 * It preserves account creation rules while keeping password hashing behind an application port.
 */

namespace App\Security\Application\UseCase;

use App\Security\Application\Input\CreateAdminUserInput;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Exception\ApplicationConflict;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\EmailAddress;

final readonly class CreateAdminUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function handle(CreateAdminUserInput $input): User
    {
        return $this->transactionManager->transactional(function () use ($input): User {
            $email = EmailAddress::fromString($input->email);

            if ($this->userRepository->findOneByEmail($email) instanceof User) {
                throw new ApplicationConflict('A user with this email already exists.');
            }

            $user = new User($email);
            $user->updateProfile($input->firstName, $input->lastName);
            $input->isActive ? $user->activate() : $user->deactivate();
            $user->updateAdminRole($input->isAdmin);
            $user->setPasswordHash($this->passwordHasher->hash($user, $input->password));

            $this->userRepository->save($user);

            return $user;
        });
    }
}

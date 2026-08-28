<?php

declare(strict_types=1);

/*
 * Application use case for partial admin user updates.
 * It owns bootstrap admin protection while leaving HTTP parsing and validation to the API adapter.
 */

namespace App\Security\Application\UseCase;

use App\Security\Application\Input\UpdateAdminUserInput;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;

final readonly class UpdateAdminUser
{
    public function __construct(
        private PasswordHasherInterface $passwordHasher,
        private UserRepositoryInterface $userRepository,
        private string $adminBootstrapEmail,
        private TransactionManagerInterface $transactionManager,
    ) {
    }

    public function handle(User $user, User $currentUser, UpdateAdminUserInput $input): User
    {
        return $this->transactionManager->transactional(function () use ($user, $currentUser, $input): User {
            $bootstrapAdmin = $user->isBootstrapAdmin($this->adminBootstrapEmail);

            if ($input->has('firstName')) {
                $user->updateProfile($input->firstName, $user->lastName());
            }

            if ($input->has('lastName')) {
                $user->updateProfile($user->firstName(), $input->lastName);
            }

            if ($this->canUpdateActiveFlag($user, $currentUser, $input, $bootstrapAdmin)) {
                ($input->isActive ?? false) ? $user->activate() : $user->deactivate();
            }

            if ($input->has('isAdmin')) {
                $bootstrapAdmin ? $user->grantAdmin() : $user->updateAdminRole((bool) $input->isAdmin);
            }

            if ($input->has('password') && $input->password !== null) {
                $user->setPasswordHash($this->passwordHasher->hash($user, $input->password));
            }

            $this->userRepository->save($user);

            return $user;
        });
    }

    private function canUpdateActiveFlag(User $user, User $currentUser, UpdateAdminUserInput $input, bool $bootstrapAdmin): bool
    {
        if (!$input->has('isActive')) {
            return false;
        }

        if ($bootstrapAdmin && $currentUser->getId()->equals($user->getId()) && $input->isActive === false) {
            return false;
        }

        return !$bootstrapAdmin || $input->isActive === true;
    }
}

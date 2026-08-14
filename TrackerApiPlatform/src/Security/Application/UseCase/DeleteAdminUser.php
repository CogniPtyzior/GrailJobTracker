<?php

declare(strict_types=1);

/*
 * Application use case for deleting an admin-managed user account.
 * It owns bootstrap admin deletion guards and delegates persistence to the user repository port.
 */

namespace App\Security\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Exception\ApplicationBadRequest;

final readonly class DeleteAdminUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private string $adminBootstrapEmail,
    ) {
    }

    public function handle(User $user, User $currentUser): void
    {
        if ($currentUser->getId()->equals($user->getId()) || $user->isBootstrapAdmin($this->adminBootstrapEmail)) {
            throw new ApplicationBadRequest('The bootstrap admin cannot be deleted.');
        }

        $this->userRepository->remove($user);
        $this->userRepository->flush();
    }
}


<?php

namespace App\Admin\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;

/**
 * Application use case that deletes an admin-managed user account.
 */
final class DeleteAdminUser
{
    public function __construct(private readonly UserRepositoryInterface $userRepository)
    {
    }

    public function handle(User $user): void
    {
        $this->userRepository->remove($user);
        $this->userRepository->flush();
    }
}
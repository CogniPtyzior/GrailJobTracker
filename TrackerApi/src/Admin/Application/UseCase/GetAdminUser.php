<?php

namespace App\Admin\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Domain\ValueObject\UserId;

/**
 * Application use case that retrieves an admin-managed user by id.
 */
final class GetAdminUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(UserId $id): ?User
    {
        return $this->userRepository->getById($id);
    }
}

<?php

declare(strict_types=1);

/*
 * Application use case for loading a user managed by the admin API.
 * It centralizes repository access so API providers and processors do not talk to persistence directly.
 */

namespace App\Security\Application\UseCase;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Security\Domain\ValueObject\UserId;

final readonly class GetAdminUser
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function handle(UserId $id): ?User
    {
        return $this->userRepository->getById($id);
    }
}

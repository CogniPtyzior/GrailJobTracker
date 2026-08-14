<?php

declare(strict_types=1);

/*
 * Mapper from user domain objects to admin API outputs.
 * It is the explicit boundary between the security domain model and API Platform serialization contracts.
 */

namespace App\Security\Api\Mapper;

use App\Security\Api\Output\AdminUserCollectionOutput;
use App\Security\Api\Output\AdminUserItemOutput;
use App\Security\Api\Output\AdminUserOutput;
use App\Security\Application\Result\SearchUsersResult;
use App\Security\Domain\Entity\User;
use DateTimeInterface;

final readonly class AdminUserApiMapper
{
    public function toOutput(User $user): AdminUserOutput
    {
        return new AdminUserOutput(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            firstName: $user->firstName()?->value(),
            lastName: $user->lastName()?->value(),
            isActive: $user->isActive(),
            roles: $user->getRoles(),
            createdAt: $user->getCreatedAt()->format(DateTimeInterface::ATOM),
            lastLoginAt: $user->getLastLoginAt()?->format(DateTimeInterface::ATOM),
        );
    }

    public function toItemOutput(User $user): AdminUserItemOutput
    {
        return new AdminUserItemOutput($this->toOutput($user));
    }

    public function toCollectionOutput(SearchUsersResult $result, int $page, int $pageSize): AdminUserCollectionOutput
    {
        return new AdminUserCollectionOutput(
            items: array_map($this->toOutput(...), $result->items),
            page: $page,
            pageSize: $pageSize,
            total: $result->total,
        );
    }
}

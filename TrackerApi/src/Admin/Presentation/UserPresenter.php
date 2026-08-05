<?php

namespace App\Admin\Presentation;

use App\Admin\Application\Result\SearchUsersResult;
use App\Security\Domain\Entity\User;

final class UserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(User $user): array
    {
        return [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'isActive' => $user->isActive(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lastLoginAt' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPaginatedResult(SearchUsersResult $result, int $page, int $pageSize): array
    {
        return [
            'items' => array_map($this->present(...), $result->items),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $result->total,
        ];
    }
}

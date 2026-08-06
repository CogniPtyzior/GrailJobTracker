<?php

namespace App\Admin\Presentation;

use App\Admin\Application\Result\SearchUsersResult;
use App\Admin\Presentation\View\UserListView;
use App\Admin\Presentation\View\UserView;
use App\Security\Domain\Entity\User;

final class UserPresenter
{
    public function present(User $user): UserView
    {
        return new UserView(
            id: $user->getId()->toRfc4122(),
            email: $user->getEmail(),
            firstName: $user->firstName()?->value(),
            lastName: $user->lastName()?->value(),
            isActive: $user->isActive(),
            roles: $user->roles()->toArray(),
            createdAt: $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            lastLoginAt: $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function presentPaginatedResult(SearchUsersResult $result, int $page, int $pageSize): UserListView
    {
        return new UserListView(
            items: array_map($this->present(...), $result->items),
            page: $page,
            pageSize: $pageSize,
            total: $result->total,
        );
    }
}
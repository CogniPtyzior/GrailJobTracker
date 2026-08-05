<?php

namespace App\Admin\Application\UseCase;

use App\Admin\Application\Result\SearchUsersResult;
use App\Security\Domain\Repository\UserRepositoryInterface;

/**
 * Application use case that searches admin-managed users.
 */
final class SearchUsers
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function handle(?bool $isActive, ?string $query, int $page, int $pageSize): SearchUsersResult
    {
        $result = $this->userRepository->search($isActive, $query, $page, $pageSize);

        return new SearchUsersResult($result['items'], $result['total']);
    }
}
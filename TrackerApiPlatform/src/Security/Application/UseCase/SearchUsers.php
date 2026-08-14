<?php

declare(strict_types=1);

/*
 * Application use case for admin user listing.
 * It delegates filtering and pagination to the repository port while keeping the query intent explicit.
 */

namespace App\Security\Application\UseCase;

use App\Security\Application\Result\SearchUsersResult;
use App\Security\Domain\Repository\UserRepositoryInterface;

final readonly class SearchUsers
{
    public function __construct(private UserRepositoryInterface $userRepository)
    {
    }

    public function handle(?bool $isActive, ?string $query, int $page, int $pageSize): SearchUsersResult
    {
        $result = $this->userRepository->search($isActive, $query, $page, $pageSize);

        return new SearchUsersResult($result['items'], $result['total']);
    }
}

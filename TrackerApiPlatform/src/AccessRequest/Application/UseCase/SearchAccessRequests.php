<?php

declare(strict_types=1);

/*
 * Application use case that searches access requests for admin screens.
 * Filtering and pagination semantics are delegated to the repository port.
 */

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;

final readonly class SearchAccessRequests
{
    public function __construct(private AccessRequestRepositoryInterface $accessRequestRepository)
    {
    }

    public function handle(?string $query, int $page, int $pageSize): SearchAccessRequestsResult
    {
        $result = $this->accessRequestRepository->search($query, $page, $pageSize);

        return new SearchAccessRequestsResult($result['items'], $result['total']);
    }
}

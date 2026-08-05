<?php

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;

/**
 * Application use case that searches access requests for admin screens.
 */
final class SearchAccessRequests
{
    public function __construct(
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
    ) {
    }

    public function handle(?string $query, int $page, int $pageSize): SearchAccessRequestsResult
    {
        $result = $this->accessRequestRepository->search($query, $page, $pageSize);

        return new SearchAccessRequestsResult($result['items'], $result['total']);
    }
}
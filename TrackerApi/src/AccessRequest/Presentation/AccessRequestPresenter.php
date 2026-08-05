<?php

namespace App\AccessRequest\Presentation;

use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\AccessRequest\Domain\Entity\AccessRequest;

final class AccessRequestPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(AccessRequest $accessRequest): array
    {
        return [
            'id' => $accessRequest->getId()->toRfc4122(),
            'email' => $accessRequest->getEmail(),
            'companyName' => $accessRequest->getCompanyName(),
            'reason' => $accessRequest->getReason(),
            'firstName' => $accessRequest->getFirstName(),
            'lastName' => $accessRequest->getLastName(),
            'createdAt' => $accessRequest->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentPaginatedResult(SearchAccessRequestsResult $result, int $page, int $pageSize): array
    {
        return [
            'items' => array_map($this->present(...), $result->items),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $result->total,
        ];
    }
}

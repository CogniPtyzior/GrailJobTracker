<?php

namespace App\AccessRequest\Presentation;

use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Presentation\View\AccessRequestListView;
use App\AccessRequest\Presentation\View\AccessRequestView;

final class AccessRequestPresenter
{
    public function present(AccessRequest $accessRequest): AccessRequestView
    {
        return new AccessRequestView(
            id: $accessRequest->getId()->toRfc4122(),
            email: $accessRequest->getEmail(),
            companyName: $accessRequest->getCompanyName(),
            reason: $accessRequest->reason()->value(),
            firstName: $accessRequest->firstName()?->value(),
            lastName: $accessRequest->lastName()?->value(),
            createdAt: $accessRequest->getCreatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function presentPaginatedResult(SearchAccessRequestsResult $result, int $page, int $pageSize): AccessRequestListView
    {
        return new AccessRequestListView(
            items: array_map($this->present(...), $result->items),
            page: $page,
            pageSize: $pageSize,
            total: $result->total,
        );
    }
}
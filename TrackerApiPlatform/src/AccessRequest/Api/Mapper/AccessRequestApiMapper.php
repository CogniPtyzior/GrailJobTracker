<?php

declare(strict_types=1);

/*
 * Mapper from access request and user domain objects to admin API outputs.
 * It keeps presentation envelopes explicit and prevents API Platform metadata from leaking into the domain.
 */

namespace App\AccessRequest\Api\Mapper;

use App\AccessRequest\Api\Output\AccessRequestCollectionOutput;
use App\AccessRequest\Api\Output\AccessRequestOutput;
use App\AccessRequest\Api\Output\ApprovedAccessRequestOutput;
use App\AccessRequest\Api\Output\ApprovedAccessRequestUserOutput;
use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\Security\Domain\Entity\User;
use DateTimeInterface;

final readonly class AccessRequestApiMapper
{
    public function toOutput(AccessRequest $accessRequest): AccessRequestOutput
    {
        return new AccessRequestOutput(
            id: $accessRequest->getId()->toRfc4122(),
            email: $accessRequest->getEmail(),
            companyName: $accessRequest->getCompanyName(),
            reason: $accessRequest->reason()->value(),
            firstName: $accessRequest->firstName()?->value(),
            lastName: $accessRequest->lastName()?->value(),
            createdAt: $accessRequest->getCreatedAt()->format(DateTimeInterface::ATOM),
        );
    }

    public function toCollectionOutput(
        SearchAccessRequestsResult $result,
        int $page,
        int $pageSize,
    ): AccessRequestCollectionOutput {
        return new AccessRequestCollectionOutput(
            items: array_map($this->toOutput(...), $result->items),
            page: $page,
            pageSize: $pageSize,
            total: $result->total,
        );
    }

    public function toApprovedOutput(User $user): ApprovedAccessRequestOutput
    {
        return new ApprovedAccessRequestOutput(
            new ApprovedAccessRequestUserOutput($user->getId()->toRfc4122(), $user->getEmail()),
        );
    }
}

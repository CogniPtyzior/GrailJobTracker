<?php

declare(strict_types=1);

/*
 * Application use case deleting a public access request.
 * Admin API processors load the aggregate first, then delegate removal to this repository-backed use case.
 */

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;

final readonly class DeleteAccessRequest
{
    public function __construct(private AccessRequestRepositoryInterface $accessRequestRepository)
    {
    }

    public function handle(AccessRequest $accessRequest): void
    {
        $this->accessRequestRepository->remove($accessRequest);
        $this->accessRequestRepository->flush();
    }
}

<?php

namespace App\Admin\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;

/**
 * Application use case that deletes a public access request.
 */
final class DeleteAccessRequest
{
    public function __construct(private readonly AccessRequestRepositoryInterface $accessRequestRepository)
    {
    }

    public function handle(AccessRequest $accessRequest): void
    {
        $this->accessRequestRepository->remove($accessRequest);
        $this->accessRequestRepository->flush();
    }
}
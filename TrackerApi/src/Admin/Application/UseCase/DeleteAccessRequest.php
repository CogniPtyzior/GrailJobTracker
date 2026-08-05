<?php

namespace App\Admin\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that deletes a pending access request.
 */
final class DeleteAccessRequest
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(AccessRequest $accessRequest): void
    {
        $this->entityManager->remove($accessRequest);
        $this->entityManager->flush();
    }
}

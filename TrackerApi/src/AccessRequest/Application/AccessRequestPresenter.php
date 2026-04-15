<?php

namespace App\AccessRequest\Application;

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
}

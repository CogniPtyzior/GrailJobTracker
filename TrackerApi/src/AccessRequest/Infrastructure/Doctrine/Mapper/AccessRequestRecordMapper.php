<?php

namespace App\AccessRequest\Infrastructure\Doctrine\Mapper;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;

final class AccessRequestRecordMapper
{
    public function toDomain(AccessRequestRecord $record): AccessRequest
    {
        return AccessRequest::reconstitute(
            $record->getId(),
            $record->getEmail(),
            $record->getNormalizedEmail(),
            $record->getCompanyName(),
            $record->getReason(),
            $record->getFirstName(),
            $record->getLastName(),
            $record->getCreatedAt(),
        );
    }

    public function updateRecord(AccessRequest $accessRequest, AccessRequestRecord $record): void
    {
        $record->setId($accessRequest->getId());
        $record->setEmail($accessRequest->getEmail());
        $record->setNormalizedEmail($accessRequest->getNormalizedEmail());
        $record->setCompanyName($accessRequest->getCompanyName());
        $record->setReason($accessRequest->getReason());
        $record->setFirstName($accessRequest->getFirstName());
        $record->setLastName($accessRequest->getLastName());
        $record->setCreatedAt($accessRequest->getCreatedAt());
    }
}
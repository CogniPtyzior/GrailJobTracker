<?php

namespace App\AccessRequest\Infrastructure\Doctrine\Mapper;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

final class AccessRequestRecordMapper
{
    public function toDomain(AccessRequestRecord $record): AccessRequest
    {
        return AccessRequest::reconstitute(
            AccessRequestId::fromUuid($record->getId()),
            EmailAddress::reconstitute($record->getEmail(), $record->getNormalizedEmail()),
            $record->getCompanyName(),
            AccessRequestReason::fromString($record->getReason()),
            PersonName::fromNullable($record->getFirstName()),
            PersonName::fromNullable($record->getLastName()),
            $record->getCreatedAt(),
        );
    }

    public function updateRecord(AccessRequest $accessRequest, AccessRequestRecord $record): void
    {
        $record->setId($accessRequest->getId()->toUuid());
        $record->setEmail($accessRequest->getEmail());
        $record->setNormalizedEmail($accessRequest->getNormalizedEmail());
        $record->setCompanyName($accessRequest->getCompanyName());
        $record->setReason($accessRequest->reason()->value());
        $record->setFirstName($accessRequest->firstName()?->value());
        $record->setLastName($accessRequest->lastName()?->value());
        $record->setCreatedAt($accessRequest->getCreatedAt());
    }
}

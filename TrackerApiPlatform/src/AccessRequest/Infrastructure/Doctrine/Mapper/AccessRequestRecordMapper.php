<?php

declare(strict_types=1);

/*
 * Mapper between the access request aggregate and the Doctrine access request record.
 * It keeps the domain model independent from the preserved database schema and API contract.
 */

namespace App\AccessRequest\Infrastructure\Doctrine\Mapper;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Symfony\Component\Uid\Uuid;

final readonly class AccessRequestRecordMapper
{
    public function toDomain(AccessRequestRecord $record): AccessRequest
    {
        return AccessRequest::reconstitute(
            AccessRequestId::fromString($record->getId()->toRfc4122()),
            EmailAddress::reconstitute($record->getEmail(), $record->getNormalizedEmail()),
            AccessRequestCompanyName::fromString($record->getCompanyName()),
            AccessRequestReason::fromString($record->getReason()),
            PersonName::fromNullable($record->getFirstName()),
            PersonName::fromNullable($record->getLastName()),
            $record->getCreatedAt(),
        );
    }

    public function updateRecord(AccessRequest $accessRequest, AccessRequestRecord $record): void
    {
        $record->setId(Uuid::fromString($accessRequest->getId()->toRfc4122()));
        $record->setEmail($accessRequest->getEmail());
        $record->setNormalizedEmail($accessRequest->getNormalizedEmail());
        $record->setCompanyName($accessRequest->companyName()->value());
        $record->setReason($accessRequest->reason()->value());
        $record->setFirstName($accessRequest->firstName()?->value());
        $record->setLastName($accessRequest->lastName()?->value());
        $record->setCreatedAt($accessRequest->getCreatedAt());
    }
}

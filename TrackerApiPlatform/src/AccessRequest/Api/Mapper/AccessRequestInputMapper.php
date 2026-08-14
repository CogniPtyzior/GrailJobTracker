<?php

declare(strict_types=1);

/*
 * Mapper from API Platform access request input to the application command.
 * It isolates HTTP field names from application orchestration and domain value object construction.
 */

namespace App\AccessRequest\Api\Mapper;

use App\AccessRequest\Api\Input\CreateAccessRequestInput as ApiCreateAccessRequestInput;
use App\AccessRequest\Application\Input\CreateAccessRequestInput;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\PersonName;

final readonly class AccessRequestInputMapper
{
    public function toCreateInput(ApiCreateAccessRequestInput $input): CreateAccessRequestInput
    {
        return new CreateAccessRequestInput(
            email: $input->email,
            companyName: AccessRequestCompanyName::fromString($input->companyName),
            reason: AccessRequestReason::fromString($input->reason),
            firstName: PersonName::fromNullable($input->firstName),
            lastName: PersonName::fromNullable($input->lastName),
        );
    }
}

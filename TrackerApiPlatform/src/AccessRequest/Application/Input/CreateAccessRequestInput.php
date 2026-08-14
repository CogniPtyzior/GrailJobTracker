<?php

declare(strict_types=1);

/*
 * Application input for public access request creation.
 * API processors will build it from validated input DTOs without leaking API Platform into the application layer.
 */

namespace App\AccessRequest\Application\Input;

use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\PersonName;

final readonly class CreateAccessRequestInput
{
    public function __construct(
        public string $email,
        public AccessRequestCompanyName $companyName,
        public AccessRequestReason $reason,
        public ?PersonName $firstName,
        public ?PersonName $lastName,
    ) {
    }
}

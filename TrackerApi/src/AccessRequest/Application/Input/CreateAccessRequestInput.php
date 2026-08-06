<?php

namespace App\AccessRequest\Application\Input;

use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\PersonName;

final readonly class CreateAccessRequestInput
{
    public function __construct(
        public string $email,
        public string $companyName,
        public AccessRequestReason $reason,
        public ?PersonName $firstName,
        public ?PersonName $lastName,
    ) {
    }
}

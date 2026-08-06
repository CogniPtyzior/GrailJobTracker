<?php

namespace App\Admin\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

/**
 * Application input object used when approving an access request.
 */
final readonly class ApproveAccessRequestInput
{
    public function __construct(
        public string $password,
        public ?PersonName $firstName,
        public ?PersonName $lastName,
    ) {
    }
}

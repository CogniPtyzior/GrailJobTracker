<?php

declare(strict_types=1);

/*
 * Application input used when an administrator approves an access request.
 * It carries the password and optional profile override names without exposing API Platform details.
 */

namespace App\AccessRequest\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

final readonly class ApproveAccessRequestInput
{
    public function __construct(
        public string $password,
        public ?PersonName $firstName,
        public ?PersonName $lastName,
    ) {
    }
}

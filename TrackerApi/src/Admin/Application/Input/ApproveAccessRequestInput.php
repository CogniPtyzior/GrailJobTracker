<?php

namespace App\Admin\Application\Input;

/**
 * Application input object used when approving an access request.
 */
final readonly class ApproveAccessRequestInput
{
    public function __construct(
        public string $password,
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}

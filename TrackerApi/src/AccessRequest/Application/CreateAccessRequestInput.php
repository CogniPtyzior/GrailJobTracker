<?php

namespace App\AccessRequest\Application;

/**
 * Application input object used to create an access request without exposing HTTP payload details.
 */
final readonly class CreateAccessRequestInput
{
    public function __construct(
        public string $email,
        public string $companyName,
        public string $reason,
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
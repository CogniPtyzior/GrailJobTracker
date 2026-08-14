<?php

declare(strict_types=1);

/*
 * Serialized authenticated user data returned to the frontend.
 * It is an API output DTO, not a domain entity and not a Doctrine projection.
 */

namespace App\Security\Api\Output;

final readonly class AuthenticatedUserData
{
    /** @param list<string> $roles */
    public function __construct(
        public string $id,
        public string $email,
        public ?string $firstName,
        public ?string $lastName,
        public array $roles,
        public bool $isActive,
        public string $createdAt,
        public ?string $lastLoginAt,
    ) {
    }
}

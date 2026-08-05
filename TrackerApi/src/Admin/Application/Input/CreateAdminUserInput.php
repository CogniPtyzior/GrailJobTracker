<?php

namespace App\Admin\Application\Input;

/**
 * Application input object used to create an admin-managed user account.
 */
final readonly class CreateAdminUserInput
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $firstName,
        public ?string $lastName,
        public bool $isActive,
        public bool $isAdmin,
    ) {
    }
}

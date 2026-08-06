<?php

namespace App\Admin\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

/**
 * Application input object used to create an admin-managed user account.
 */
final readonly class CreateAdminUserInput
{
    public function __construct(
        public string $email,
        public string $password,
        public ?PersonName $firstName,
        public ?PersonName $lastName,
        public bool $isActive,
        public bool $isAdmin,
    ) {
    }
}

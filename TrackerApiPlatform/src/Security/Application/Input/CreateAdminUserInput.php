<?php

declare(strict_types=1);

/*
 * Application input for admin-created users.
 * It keeps the API payload contract outside the use case while carrying already-normalized value objects.
 */

namespace App\Security\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

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

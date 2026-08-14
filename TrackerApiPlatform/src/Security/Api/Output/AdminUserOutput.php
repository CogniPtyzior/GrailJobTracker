<?php

declare(strict_types=1);

/*
 * API output DTO for admin user items.
 * It preserves the frontend contract while keeping serializer metadata away from the user aggregate.
 */

namespace App\Security\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AdminUserOutput
{
    /**
     * @param list<string> $roles
     */
    public function __construct(
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public string $id,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public string $email,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public ?string $firstName,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public ?string $lastName,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public bool $isActive,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public array $roles,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public string $createdAt,
        #[Groups(['admin_user:list', 'admin_user:read'])]
        public ?string $lastLoginAt,
    ) {
    }
}

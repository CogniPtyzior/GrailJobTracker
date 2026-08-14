<?php

declare(strict_types=1);

/*
 * API output DTO for admin user write responses.
 * The top-level item key is kept for frontend compatibility with the legacy admin service.
 */

namespace App\Security\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AdminUserItemOutput
{
    public function __construct(
        #[Groups(['admin_user:read'])]
        public AdminUserOutput $item,
    ) {
    }
}

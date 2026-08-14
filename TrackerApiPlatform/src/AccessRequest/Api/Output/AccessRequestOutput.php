<?php

declare(strict_types=1);

/*
 * API output DTO for admin access request items.
 * It mirrors the legacy frontend fields while staying separate from the domain aggregate.
 */

namespace App\AccessRequest\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AccessRequestOutput
{
    public function __construct(
        #[Groups(['access_request:list', 'access_request:read'])]
        public string $id,
        #[Groups(['access_request:list', 'access_request:read'])]
        public string $email,
        #[Groups(['access_request:list', 'access_request:read'])]
        public string $companyName,
        #[Groups(['access_request:list', 'access_request:read'])]
        public string $reason,
        #[Groups(['access_request:list', 'access_request:read'])]
        public ?string $firstName,
        #[Groups(['access_request:list', 'access_request:read'])]
        public ?string $lastName,
        #[Groups(['access_request:list', 'access_request:read'])]
        public string $createdAt,
    ) {
    }
}

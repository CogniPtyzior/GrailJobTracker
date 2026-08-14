<?php

declare(strict_types=1);

/*
 * API output DTO for the user created or reactivated by access request approval.
 * It intentionally exposes only the fields consumed by the existing frontend call.
 */

namespace App\AccessRequest\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class ApprovedAccessRequestUserOutput
{
    public function __construct(
        #[Groups(['access_request:approved'])]
        public string $id,
        #[Groups(['access_request:approved'])]
        public string $email,
    ) {
    }
}

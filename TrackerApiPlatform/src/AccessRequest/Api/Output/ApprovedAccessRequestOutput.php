<?php

declare(strict_types=1);

/*
 * API output DTO for approved access requests.
 * The top-level item key preserves the legacy frontend contract after a user is provisioned.
 */

namespace App\AccessRequest\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class ApprovedAccessRequestOutput
{
    public function __construct(
        #[Groups(['access_request:approved'])]
        public ApprovedAccessRequestUserOutput $item,
    ) {
    }
}

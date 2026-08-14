<?php

declare(strict_types=1);

/*
 * API output DTO for admin access request collections.
 * It preserves the legacy pagination envelope used by the frontend admin screens.
 */

namespace App\AccessRequest\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AccessRequestCollectionOutput
{
    /** @param list<AccessRequestOutput> $items */
    public function __construct(
        #[Groups(['access_request:list'])]
        public array $items,
        #[Groups(['access_request:list'])]
        public int $page,
        #[Groups(['access_request:list'])]
        public int $pageSize,
        #[Groups(['access_request:list'])]
        public int $total,
    ) {
    }
}

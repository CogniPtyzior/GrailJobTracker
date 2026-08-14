<?php

declare(strict_types=1);

/*
 * API output DTO for a single tracked job item.
 * The top-level item key is kept for frontend compatibility.
 */

namespace App\TrackedJob\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class TrackedJobItemOutput
{
    public function __construct(
        #[Groups(['tracked_job:item'])]
        public TrackedJobOutput $item,
    ) {
    }
}

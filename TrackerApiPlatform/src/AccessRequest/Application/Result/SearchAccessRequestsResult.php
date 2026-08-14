<?php

declare(strict_types=1);

/*
 * Application result returned by access request searches for admin screens.
 * It carries domain aggregates and total count without exposing persistence records.
 */

namespace App\AccessRequest\Application\Result;

use App\AccessRequest\Domain\Entity\AccessRequest;

final readonly class SearchAccessRequestsResult
{
    /** @param list<AccessRequest> $items */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}

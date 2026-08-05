<?php

namespace App\AccessRequest\Application\Result;

use App\AccessRequest\Domain\Entity\AccessRequest;

/**
 * Application result returned by the access request search use case.
 */
final readonly class SearchAccessRequestsResult
{
    /**
     * @param list<AccessRequest> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}
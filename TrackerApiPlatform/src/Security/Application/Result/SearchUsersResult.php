<?php

declare(strict_types=1);

/*
 * Application result for admin user searches.
 * It carries domain users and pagination metadata without exposing API Platform output DTOs.
 */

namespace App\Security\Application\Result;

use App\Security\Domain\Entity\User;

final readonly class SearchUsersResult
{
    /**
     * @param list<User> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}

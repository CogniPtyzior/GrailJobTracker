<?php

declare(strict_types=1);

/*
 * Contract for public access request submission throttling.
 * It lets API processors depend on a small policy port instead of the Symfony RateLimiter implementation.
 */

namespace App\AccessRequest\Api\RateLimit;

interface AccessRequestSubmissionLimiterInterface
{
    public function enforce(): void;
}

<?php

declare(strict_types=1);

/*
 * Application factory for opening tracked jobs.
 * It keeps ownership creation in one place before API processors and use cases persist the aggregate.
 */

namespace App\TrackedJob\Application\Factory;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;

final readonly class TrackedJobFactory
{
    public function create(User $owner): TrackedJob
    {
        return TrackedJob::openFor($owner->getId());
    }
}

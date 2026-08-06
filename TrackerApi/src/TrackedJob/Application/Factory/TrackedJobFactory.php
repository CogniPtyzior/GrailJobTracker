<?php

namespace App\TrackedJob\Application\Factory;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;

final class TrackedJobFactory
{
    public function create(User $owner): TrackedJob
    {
        return TrackedJob::openFor($owner);
    }
}

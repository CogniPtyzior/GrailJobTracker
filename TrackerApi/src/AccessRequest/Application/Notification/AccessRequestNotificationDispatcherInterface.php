<?php

namespace App\AccessRequest\Application\Notification;

use App\AccessRequest\Domain\Entity\AccessRequest;

interface AccessRequestNotificationDispatcherInterface
{
    public function dispatchCreated(AccessRequest $accessRequest): void;
}
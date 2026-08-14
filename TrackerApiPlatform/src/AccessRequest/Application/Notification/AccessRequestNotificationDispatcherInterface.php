<?php

declare(strict_types=1);

/*
 * Application port for dispatching access request creation notifications.
 * Messenger integration will implement this contract in a later infrastructure step.
 */

namespace App\AccessRequest\Application\Notification;

use App\AccessRequest\Domain\Entity\AccessRequest;

interface AccessRequestNotificationDispatcherInterface
{
    public function dispatchCreated(AccessRequest $accessRequest): void;
}

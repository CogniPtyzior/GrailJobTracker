<?php

declare(strict_types=1);

/*
 * Temporary no-op notification dispatcher used until the Messenger adapter is introduced.
 * It keeps the application service graph valid without coupling the use case to infrastructure.
 */

namespace App\AccessRequest\Application\Notification;

use App\AccessRequest\Domain\Entity\AccessRequest;

final readonly class NullAccessRequestNotificationDispatcher implements AccessRequestNotificationDispatcherInterface
{
    public function dispatchCreated(AccessRequest $accessRequest): void
    {
    }
}

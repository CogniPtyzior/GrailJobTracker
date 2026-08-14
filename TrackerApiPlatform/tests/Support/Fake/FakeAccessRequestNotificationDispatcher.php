<?php

declare(strict_types=1);

/*
 * Fake access request notification dispatcher for application tests.
 * It records dispatched requests without requiring Messenger or Mailer infrastructure.
 */

namespace App\Tests\Support\Fake;

use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcherInterface;
use App\AccessRequest\Domain\Entity\AccessRequest;

final class FakeAccessRequestNotificationDispatcher implements AccessRequestNotificationDispatcherInterface
{
    /** @var list<AccessRequest> */
    public array $createdNotifications = [];

    public function dispatchCreated(AccessRequest $accessRequest): void
    {
        $this->createdNotifications[] = $accessRequest;
    }
}

<?php

declare(strict_types=1);

/*
 * Logout response subscriber for Symfony security logout.
 * It lets the firewall invalidate the session while keeping the API response JSON-only.
 */

namespace App\Security\Infrastructure\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final readonly class ApiLogoutSuccessHandler implements EventSubscriberInterface
{
    public function __construct(private SecurityJsonResponseFactory $responseFactory)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $event->setResponse($this->responseFactory->success([
            'message' => 'Logout successful.',
        ], Response::HTTP_OK));
    }
}

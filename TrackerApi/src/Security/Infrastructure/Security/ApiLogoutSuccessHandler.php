<?php

declare(strict_types=1);

namespace App\Security\Infrastructure\Security;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Event\LogoutEvent;

final class ApiLogoutSuccessHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogout(LogoutEvent $event): void
    {
        // Return a clean JSON response for API clients after logout.
        $event->setResponse(new JsonResponse([
            'message' => 'Logout successful.',
        ], JsonResponse::HTTP_OK));
    }
}
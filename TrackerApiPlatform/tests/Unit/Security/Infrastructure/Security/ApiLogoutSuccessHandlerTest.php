<?php

declare(strict_types=1);

/*
 * Unit tests for the Symfony logout response handler.
 * They protect the frontend JSON contract while the firewall remains responsible for session invalidation.
 */

use App\Security\Infrastructure\Security\ApiLogoutSuccessHandler;
use App\Security\Infrastructure\Security\SecurityJsonResponseFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\LogoutEvent;

it('returns the frontend-compatible logout response', function (): void {
    $event = new LogoutEvent(new Request(), null);

    (new ApiLogoutSuccessHandler(new SecurityJsonResponseFactory()))->onLogout($event);

    expect($event->getResponse()?->getStatusCode())->toBe(200)
        ->and($event->getResponse()?->getContent())->toBe('{"message":"Logout successful."}');
});

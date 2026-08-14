<?php

declare(strict_types=1);

/*
 * Integration tests for authentication route exposure.
 * They verify API Platform and Symfony Security publish the expected frontend paths without touching persistence.
 */

use Symfony\Component\Routing\RouterInterface;


it('exposes the current user operation through API Platform', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('GET');

    $parameters = $router->match('/api/auth/me');

    expect($parameters['_route'])->toContain('auth_me');
});

it('exposes the login operation through API Platform metadata', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('POST');

    $parameters = $router->match('/api/auth/login');

    expect($parameters['_route'])->toContain('auth_login');
});

it('keeps logout exposed through the Symfony security firewall', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('POST');

    $parameters = $router->match('/api/auth/logout');

    expect($parameters['_route'])->toBe('_logout_main');
});

<?php

declare(strict_types=1);

/*
 * Integration tests for reference data route exposure.
 * They ensure API Platform publishes the existing frontend path through the new resource.
 */

use Symfony\Component\Routing\RouterInterface;

it('exposes reference data through API Platform', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('GET');

    $parameters = $router->match('/api/reference-data');

    expect($parameters['_route'])->toContain('reference_data_get');
});

<?php

declare(strict_types=1);

/*
 * Unit tests for the API Platform login guard processor.
 * The test documents that the real login flow belongs to Symfony Security, not to a processor.
 */

use ApiPlatform\Metadata\Post;
use App\Security\Api\Processor\LoginHandledBySecurityProcessor;

it('fails fast if a login request reaches the API Platform processor', function (): void {
    $processor = new LoginHandledBySecurityProcessor();

    $processor->process(new stdClass(), new Post());
})->throws(LogicException::class, 'Login requests must be handled by the Symfony security authenticator.');

<?php

declare(strict_types=1);

/*
 * Unit tests for Symfony security configuration.
 * They ensure the backend uses the custom JSON authenticator and keeps login throttling enabled.
 */

use App\Security\Infrastructure\Security\JsonLoginAuthenticator;
use Symfony\Component\Yaml\Yaml;

it('configures the custom JSON login authenticator', function (): void {
    $config = Yaml::parseFile(dirname(__DIR__, 5).'/config/packages/security.yaml');
    $mainFirewall = $config['security']['firewalls']['main'];

    expect($mainFirewall['custom_authenticators'])->toContain(JsonLoginAuthenticator::class)
        ->and($mainFirewall)->toHaveKey('login_throttling')
        ->and($mainFirewall['login_throttling']['max_attempts'])->toBe(5)
        ->and($mainFirewall['provider'])->toBe('app_user_provider');
});


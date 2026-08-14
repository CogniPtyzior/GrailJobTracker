<?php

declare(strict_types=1);

/*
 * Unit tests for API Platform exception mapping.
 * They prove transport status codes are configured at the API boundary instead of inside domain or application code.
 */

use App\Shared\Application\Exception\ApplicationConflict;
use App\Shared\Application\Exception\ApplicationException;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\Shared\Domain\Exception\DomainException;
use Symfony\Component\Yaml\Yaml;

it('maps shared business exceptions through API Platform configuration', function (): void {
    $configPath = dirname(__DIR__, 5).'/config/packages/api_platform.yaml';
    $config = Yaml::parseFile($configPath);

    expect($config['api_platform']['exception_to_status'])
        ->toHaveKey(DomainException::class, 400)
        ->toHaveKey(ApplicationException::class, 400)
        ->toHaveKey(ApplicationNotFound::class, 404)
        ->toHaveKey(ApplicationConflict::class, 409)
        ->toHaveKey(InvalidApplicationCommand::class, 422);
});


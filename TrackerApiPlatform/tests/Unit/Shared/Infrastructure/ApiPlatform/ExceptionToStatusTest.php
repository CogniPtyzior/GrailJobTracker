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

    $exceptionStatuses = $config['api_platform']['exception_to_status'];
    $exceptionOrder = array_keys($exceptionStatuses);
    $applicationParentIndex = array_search(ApplicationException::class, $exceptionOrder, true);

    expect($exceptionStatuses)
        ->toHaveKey(ApplicationNotFound::class, 404)
        ->toHaveKey(ApplicationConflict::class, 409)
        ->toHaveKey(InvalidApplicationCommand::class, 422)
        ->toHaveKey(ApplicationException::class, 400)
        ->toHaveKey(DomainException::class, 400);

    expect(array_search(ApplicationNotFound::class, $exceptionOrder, true))->toBeLessThan($applicationParentIndex)
        ->and(array_search(ApplicationConflict::class, $exceptionOrder, true))->toBeLessThan($applicationParentIndex)
        ->and(array_search(InvalidApplicationCommand::class, $exceptionOrder, true))->toBeLessThan($applicationParentIndex);
});



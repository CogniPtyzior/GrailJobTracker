<?php

declare(strict_types=1);

/*
 * Guard processor for the API Platform login operation.
 * Symfony Security must intercept successful or failed login requests before this processor is reached.
 */

namespace App\Security\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use LogicException;

/** @implements ProcessorInterface<object, never> */
final readonly class LoginHandledBySecurityProcessor implements ProcessorInterface
{
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): never
    {
        throw new LogicException('Login requests must be handled by the Symfony security authenticator.');
    }
}

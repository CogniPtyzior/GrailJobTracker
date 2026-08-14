<?php

declare(strict_types=1);

/*
 * API Platform processor for admin user creation.
 * It maps validated input to an application use case and returns the frontend-compatible item envelope.
 */

namespace App\Security\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Input\CreateAdminUserInput;
use App\Security\Api\Mapper\AdminUserApiMapper;
use App\Security\Api\Mapper\AdminUserInputMapper;
use App\Security\Api\Output\AdminUserItemOutput;
use App\Security\Application\UseCase\CreateAdminUser;
use App\Shared\Application\Exception\InvalidApplicationCommand;

/** @implements ProcessorInterface<CreateAdminUserInput, AdminUserItemOutput> */
final readonly class CreateAdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private CreateAdminUser $createAdminUser,
        private AdminUserInputMapper $inputMapper,
        private AdminUserApiMapper $apiMapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminUserItemOutput
    {
        if (!$data instanceof CreateAdminUserInput) {
            throw new InvalidApplicationCommand('Invalid admin user creation payload.');
        }

        return $this->apiMapper->toItemOutput($this->createAdminUser->handle($this->inputMapper->toCreateInput($data)));
    }
}

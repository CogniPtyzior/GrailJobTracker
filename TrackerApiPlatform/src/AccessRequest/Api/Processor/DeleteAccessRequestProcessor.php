<?php

declare(strict_types=1);

/*
 * API Platform processor for admin access request deletion.
 * It keeps deletion orchestration thin and delegates business persistence to the application use case.
 */

namespace App\AccessRequest\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\AccessRequest\Application\UseCase\DeleteAccessRequest;
use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\Shared\Application\Exception\ApplicationNotFound;
use InvalidArgumentException;

/** @implements ProcessorInterface<null, null> */
final readonly class DeleteAccessRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private GetAccessRequest $getAccessRequest,
        private DeleteAccessRequest $deleteAccessRequest,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $accessRequest = $this->loadAccessRequest($uriVariables['id'] ?? null);
        $this->deleteAccessRequest->handle($accessRequest);

        return null;
    }

    private function loadAccessRequest(mixed $id): AccessRequest
    {
        if (!is_string($id)) {
            throw new ApplicationNotFound('Access request not found.');
        }

        try {
            $accessRequestId = AccessRequestId::fromString($id);
        } catch (InvalidArgumentException) {
            throw new ApplicationNotFound('Access request not found.');
        }

        return $this->getAccessRequest->handle($accessRequestId)
            ?? throw new ApplicationNotFound('Access request not found.');
    }
}

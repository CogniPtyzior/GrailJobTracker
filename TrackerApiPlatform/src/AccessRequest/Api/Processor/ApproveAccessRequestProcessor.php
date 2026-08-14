<?php

declare(strict_types=1);

/*
 * API Platform processor for admin access request approval.
 * It loads the request, maps validated input to the application use case and returns the legacy user envelope.
 */

namespace App\AccessRequest\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\AccessRequest\Api\Input\ApproveAccessRequestInput as ApiApproveAccessRequestInput;
use App\AccessRequest\Api\Mapper\AccessRequestApiMapper;
use App\AccessRequest\Api\Output\ApprovedAccessRequestOutput;
use App\AccessRequest\Application\Input\ApproveAccessRequestInput;
use App\AccessRequest\Application\UseCase\ApproveAccessRequest;
use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\Shared\Domain\ValueObject\PersonName;
use InvalidArgumentException;

/** @implements ProcessorInterface<ApiApproveAccessRequestInput, ApprovedAccessRequestOutput> */
final readonly class ApproveAccessRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private GetAccessRequest $getAccessRequest,
        private ApproveAccessRequest $approveAccessRequest,
        private AccessRequestApiMapper $apiMapper,
    ) {
    }

    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ApprovedAccessRequestOutput {
        if (!$data instanceof ApiApproveAccessRequestInput) {
            throw new InvalidApplicationCommand('Invalid access request approval payload.');
        }

        $accessRequest = $this->loadAccessRequest($uriVariables['id'] ?? null);
        $user = $this->approveAccessRequest->handle(
            $accessRequest,
            new ApproveAccessRequestInput(
                password: $data->password,
                firstName: PersonName::fromNullable($data->firstName),
                lastName: PersonName::fromNullable($data->lastName),
            ),
        );

        return $this->apiMapper->toApprovedOutput($user);
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

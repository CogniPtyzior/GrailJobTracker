<?php

declare(strict_types=1);

/*
 * API Platform processor for public access request creation.
 * It applies submission throttling, delegates creation to the application layer and returns the legacy empty JSON body.
 */

namespace App\AccessRequest\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\AccessRequest\Api\Input\CreateAccessRequestInput;
use App\AccessRequest\Api\Mapper\AccessRequestInputMapper;
use App\AccessRequest\Api\RateLimit\AccessRequestSubmissionLimiterInterface;
use App\AccessRequest\Application\UseCase\CreateAccessRequest;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** @implements ProcessorInterface<CreateAccessRequestInput, JsonResponse> */
final readonly class CreateAccessRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private AccessRequestSubmissionLimiterInterface $submissionLimiter,
        private AccessRequestInputMapper $inputMapper,
        private CreateAccessRequest $createAccessRequest,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): JsonResponse
    {
        if (!$data instanceof CreateAccessRequestInput) {
            throw new InvalidApplicationCommand('Invalid access request create payload.');
        }

        $this->submissionLimiter->enforce();
        $this->createAccessRequest->handle($this->inputMapper->toCreateInput($data));

        return new JsonResponse([], Response::HTTP_CREATED);
    }
}

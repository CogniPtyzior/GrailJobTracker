<?php

namespace App\AccessRequest\Presentation;

use App\AccessRequest\Application\UseCase\CreateAccessRequest;
use App\AccessRequest\Presentation\Payload\CreateAccessRequestPayload;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\RequestPayloadMapper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Public HTTP entry point for access request submissions.
 */
#[Route('/api/access-requests')]
final class PublicAccessRequestController extends AbstractController
{
    public function __construct(
        private readonly RequestPayloadMapper $payloads,
        private readonly CreateAccessRequest $createAccessRequest,
        private readonly RateLimiterFactoryInterface $accessRequestSubmissionLimiter,
    ) {
    }

    #[OA\Post(path: '/api/access-requests', summary: 'Create an access request.', tags: ['Access requests'])]
    #[Route('', name: 'api_access_requests_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->enforceRateLimit($request);

        /** @var CreateAccessRequestPayload $payload */
        $payload = $this->payloads->fromRequest($request, CreateAccessRequestPayload::class);
        $this->createAccessRequest->handle($payload->toInput());

        return ApiJsonResponse::success([], Response::HTTP_CREATED);
    }

    private function enforceRateLimit(Request $request): void
    {
        $key = hash('sha256', (string) $request->getClientIp());
        $limit = $this->accessRequestSubmissionLimiter->create($key)->consume();

        if ($limit->isAccepted()) {
            return;
        }

        $retryAfter = max(1, $limit->getRetryAfter()->getTimestamp() - time());

        throw new TooManyRequestsHttpException(
            $retryAfter,
            'Too many access request submissions. Please retry later.',
        );
    }
}


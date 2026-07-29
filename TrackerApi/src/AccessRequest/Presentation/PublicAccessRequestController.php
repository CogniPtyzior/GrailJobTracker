<?php

namespace App\AccessRequest\Presentation;

use App\AccessRequest\Application\AccessRequestNotificationDispatcher;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Presentation\Payload\CreateAccessRequestPayload;
use App\Security\Application\EmailNormalizer;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use Doctrine\ORM\EntityManagerInterface;
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
        private readonly PayloadValidator $payloadValidator,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessRequestNotificationDispatcher $notificationDispatcher,
        private readonly RateLimiterFactoryInterface $accessRequestSubmissionLimiter,
    ) {
    }

    #[OA\Post(path: '/api/access-requests', summary: 'Create an access request.', tags: ['Access requests'])]
    #[Route('', name: 'api_access_requests_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $this->enforceRateLimit($request);

        $payload = $this->payloadValidator->validateRequest($request, CreateAccessRequestPayload::constraint());

        $accessRequest = new AccessRequest(
            $payload['email'],
            $this->emailNormalizer->normalize($payload['email']),
            trim($payload['companyName']),
            trim($payload['reason']),
        );

        $accessRequest->setFirstName($payload['firstName'] ?? null);
        $accessRequest->setLastName($payload['lastName'] ?? null);

        $this->entityManager->persist($accessRequest);
        $this->entityManager->flush();
        $this->notificationDispatcher->dispatchCreated($accessRequest);

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

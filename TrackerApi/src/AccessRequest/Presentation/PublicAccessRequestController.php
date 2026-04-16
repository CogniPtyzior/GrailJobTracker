<?php

namespace App\AccessRequest\Presentation;

use App\AccessRequest\Application\AccessRequestNotificationSender;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\Security\Application\EmailNormalizer;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/access-requests')]
final class PublicAccessRequestController extends AbstractController
{
    public function __construct(
        private readonly PayloadValidator $payloadValidator,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly EntityManagerInterface $entityManager,
        private readonly AccessRequestNotificationSender $notificationSender,
    ) {
    }

    #[OA\Post(path: '/api/access-requests', summary: 'Create an access request.', tags: ['Access requests'])]
    #[Route('', name: 'api_access_requests_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $payload = $this->payloadValidator->validateRequest($request, new Assert\Collection([
            'fields' => [
                'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
                'companyName' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 255)],
                'reason' => [new Assert\NotBlank(), new Assert\Type('string')],
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
            ],
            'allowMissingFields' => false,
            'allowExtraFields' => false,
        ]));

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
        $this->notificationSender->sendCreatedNotification($accessRequest);

        return ApiJsonResponse::success([], Response::HTTP_CREATED);
    }
}

<?php

namespace App\Admin\Presentation;

use App\AccessRequest\Presentation\AccessRequestPresenter;
use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\AccessRequest\Application\UseCase\SearchAccessRequests;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\Admin\Application\UseCase\ApproveAccessRequest;
use App\Admin\Application\UseCase\DeleteAccessRequest;
use App\Admin\Presentation\Payload\ApproveAccessRequestPayload;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\RequestPayloadMapper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;

#[Route('/api/admin/access-requests')]
final class AdminAccessRequestController extends AbstractController
{
    public function __construct(
        private readonly AccessRequestPresenter $presenter,
        private readonly SearchAccessRequests $searchAccessRequests,
        private readonly GetAccessRequest $getAccessRequest,
        private readonly RequestPayloadMapper $payloads,
        private readonly ApproveAccessRequest $approveAccessRequest,
        private readonly DeleteAccessRequest $deleteAccessRequest,
    ) {
    }

    #[OA\Get(path: '/api/admin/access-requests', summary: 'List access requests.', tags: ['Admin access requests'])]
    #[Route('', name: 'api_admin_access_requests_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max((int) $request->query->get('page', 1), 1);
        $pageSize = min(max((int) $request->query->get('pageSize', 10), 1), 100);

        $result = $this->searchAccessRequests->handle($request->query->get('query'), $page, $pageSize);

        return ApiJsonResponse::success($this->presenter->presentPaginatedResult($result, $page, $pageSize));
    }

    #[OA\Post(
        path: '/api/admin/access-requests/{id}/approve',
        summary: 'Approve an access request and create a user if needed.',
        tags: ['Admin access requests'],
    )]
    #[Route('/{id}/approve', name: 'api_admin_access_requests_approve', methods: ['POST'])]
    public function approve(string $id, Request $request): Response
    {
        $accessRequest = $this->findAccessRequest($id);

        if (!$accessRequest instanceof AccessRequest) {
            return ApiJsonResponse::error('Access request not found.', Response::HTTP_NOT_FOUND);
        }

        /** @var ApproveAccessRequestPayload $payload */
        $payload = $this->payloads->fromRequest($request, ApproveAccessRequestPayload::class);
        $user = $this->approveAccessRequest->handle($accessRequest, $payload->toInput());

        return ApiJsonResponse::success([
            'item' => [
                'id' => $user->getId()->toRfc4122(),
                'email' => $user->getEmail(),
            ],
        ]);
    }

    #[OA\Delete(path: '/api/admin/access-requests/{id}', summary: 'Delete an access request.', tags: ['Admin access requests'])]
    #[Route('/{id}', name: 'api_admin_access_requests_delete', methods: ['DELETE'])]
    public function delete(string $id): Response
    {
        $accessRequest = $this->findAccessRequest($id);

        if (!$accessRequest instanceof AccessRequest) {
            return ApiJsonResponse::error('Access request not found.', Response::HTTP_NOT_FOUND);
        }

        $this->deleteAccessRequest->handle($accessRequest);

        return ApiJsonResponse::success(status: Response::HTTP_NO_CONTENT);
    }

    private function findAccessRequest(string $id): ?AccessRequest
    {
        try {
            return $this->getAccessRequest->handle(AccessRequestId::fromString($id));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}

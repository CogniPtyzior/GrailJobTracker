<?php

namespace App\Admin\Presentation;

use App\Admin\Application\UseCase\CreateAdminUser;
use App\Admin\Application\Exception\AdminUserAlreadyExists;
use App\Admin\Application\UseCase\GetAdminUser;
use App\Admin\Application\UseCase\DeleteAdminUser;
use App\Admin\Application\UseCase\SearchUsers;
use App\Admin\Application\UseCase\UpdateAdminUser;
use App\Admin\Presentation\Payload\CreateAdminUserPayload;
use App\Admin\Presentation\Payload\UpdateAdminUserPayload;
use App\Security\Domain\Entity\User;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\RequestPayloadMapper;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;

#[Route('/api/admin/users')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserPresenter $presenter,
        private readonly SearchUsers $searchUsers,
        private readonly GetAdminUser $getAdminUser,
        private readonly RequestPayloadMapper $payloads,
        private readonly CreateAdminUser $createAdminUser,
        private readonly UpdateAdminUser $updateAdminUser,
        private readonly DeleteAdminUser $deleteAdminUser,
        private readonly string $adminBootstrapEmail,
    ) {
    }

    #[OA\Get(path: '/api/admin/users', summary: 'List users for admin.', tags: ['Admin users'])]
    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max((int) $request->query->get('page', 1), 1);
        $pageSize = min(max((int) $request->query->get('pageSize', 10), 1), 100);

        $result = $this->searchUsers->handle(
            match ($request->query->get('isActive')) {
                'true' => true,
                'false' => false,
                default => null,
            },
            $request->query->get('query'),
            $page,
            $pageSize,
        );

        return ApiJsonResponse::success($this->presenter->presentPaginatedResult($result, $page, $pageSize));
    }

    #[OA\Post(path: '/api/admin/users', summary: 'Create a user.', tags: ['Admin users'])]
    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        /** @var CreateAdminUserPayload $payload */
        $payload = $this->payloads->fromRequest($request, CreateAdminUserPayload::class);
        try {
            $user = $this->createAdminUser->handle($payload->toInput());
        } catch (AdminUserAlreadyExists $exception) {
            return ApiJsonResponse::error($exception->getMessage(), Response::HTTP_CONFLICT);
        }

        return ApiJsonResponse::success(['item' => $this->presenter->present($user)], Response::HTTP_CREATED);
    }

    #[OA\Put(path: '/api/admin/users/{id}', summary: 'Update a user.', tags: ['Admin users'])]
    #[Route('/{id}', name: 'api_admin_users_update', methods: ['PUT'])]
    public function update(string $id, Request $request, #[CurrentUser] User $currentUser): Response
    {
        $user = $this->findUser($id);

        if (!$user instanceof User) {
            return ApiJsonResponse::error('User not found.', Response::HTTP_NOT_FOUND);
        }

        /** @var UpdateAdminUserPayload $payload */
        $payload = $this->payloads->fromRequest($request, UpdateAdminUserPayload::class);
        $this->updateAdminUser->handle($user, $currentUser, $payload->toInput());

        return ApiJsonResponse::success(['item' => $this->presenter->present($user)]);
    }

    #[OA\Delete(path: '/api/admin/users/{id}', summary: 'Delete a user.', tags: ['Admin users'])]
    #[Route('/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function delete(string $id, #[CurrentUser] User $currentUser): Response
    {
        $user = $this->findUser($id);

        if (!$user instanceof User) {
            return ApiJsonResponse::error('User not found.', Response::HTTP_NOT_FOUND);
        }

        if ($currentUser->getId()->equals($user->getId()) || $user->isBootstrapAdmin($this->adminBootstrapEmail)) {
            return ApiJsonResponse::error('The bootstrap admin cannot be deleted.', Response::HTTP_BAD_REQUEST);
        }

        $this->deleteAdminUser->handle($user);

        return ApiJsonResponse::success(status: Response::HTTP_NO_CONTENT);
    }

    private function findUser(string $id): ?User
    {
        try {
            return $this->getAdminUser->handle(Uuid::fromString($id));
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}

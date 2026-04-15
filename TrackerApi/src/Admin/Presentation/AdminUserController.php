<?php

namespace App\Admin\Presentation;

use App\Admin\Application\UserPresenter;
use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Doctrine\UserRepository;
use App\Shared\Infrastructure\Http\ApiJsonResponse;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/admin/users')]
final class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserPresenter $presenter,
        private readonly PayloadValidator $payloadValidator,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $adminBootstrapEmail,
    ) {
    }

    #[OA\Get(path: '/api/admin/users', summary: 'List users for admin.', tags: ['Admin users'])]
    #[Route('', name: 'api_admin_users_list', methods: ['GET'])]
    public function list(Request $request): Response
    {
        $page = max((int) $request->query->get('page', 1), 1);
        $pageSize = min(max((int) $request->query->get('pageSize', 10), 1), 100);

        $result = $this->userRepository->search(
            match ($request->query->get('isActive')) {
                'true' => true,
                'false' => false,
                default => null,
            },
            $request->query->get('query'),
            $page,
            $pageSize,
        );

        return ApiJsonResponse::success([
            'items' => array_map($this->presenter->present(...), $result['items']),
            'page' => $page,
            'pageSize' => $pageSize,
            'total' => $result['total'],
        ]);
    }

    #[OA\Post(path: '/api/admin/users', summary: 'Create a user.', tags: ['Admin users'])]
    #[Route('', name: 'api_admin_users_create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $payload = $this->payloadValidator->validateRequest($request, new Assert\Collection([
            'fields' => [
                'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
                'password' => $this->passwordConstraint(),
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'isActive' => new Assert\Optional([new Assert\Type('bool')]),
                'isAdmin' => new Assert\Optional([new Assert\Type('bool')]),
            ],
            'allowMissingFields' => false,
            'allowExtraFields' => false,
        ]));

        $normalizedEmail = $this->emailNormalizer->normalize($payload['email']);

        if ($this->userRepository->findOneByNormalizedEmail($normalizedEmail) instanceof User) {
            return ApiJsonResponse::error('A user with this email already exists.', Response::HTTP_CONFLICT);
        }

        $user = new User($payload['email'], $normalizedEmail);
        $user->setFirstName($payload['firstName'] ?? null);
        $user->setLastName($payload['lastName'] ?? null);
        $user->setIsActive($payload['isActive'] ?? true);
        $user->setRoles(($payload['isAdmin'] ?? false) ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

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

        $payload = $this->payloadValidator->validateRequest($request, new Assert\Collection([
            'fields' => [
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'isActive' => new Assert\Optional([new Assert\Type('bool')]),
                'isAdmin' => new Assert\Optional([new Assert\Type('bool')]),
                'password' => new Assert\Optional([$this->passwordConstraint()]),
            ],
            'allowMissingFields' => true,
            'allowExtraFields' => false,
        ]));

        $bootstrapAdmin = $user->isBootstrapAdmin($this->adminBootstrapEmail);

        $user->setFirstName($payload['firstName'] ?? $user->getFirstName());
        $user->setLastName($payload['lastName'] ?? $user->getLastName());

        if (array_key_exists('isActive', $payload) && !($bootstrapAdmin && $currentUser->getId()->equals($user->getId()) && $payload['isActive'] === false)) {
            if (!$bootstrapAdmin || $payload['isActive'] === true) {
                $user->setIsActive($payload['isActive']);
            }
        }

        if (array_key_exists('isAdmin', $payload)) {
            if ($bootstrapAdmin) {
                $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
            } else {
                $user->setRoles($payload['isAdmin'] ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER']);
            }
        }

        if (isset($payload['password']) && is_string($payload['password'])) {
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload['password']));
        }

        $this->entityManager->flush();

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

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return ApiJsonResponse::success(status: Response::HTTP_NO_CONTENT);
    }

    private function findUser(string $id): ?User
    {
        if (!Uuid::isValid($id)) {
            return null;
        }

        return $this->userRepository->getById(Uuid::fromString($id));
    }

    private function passwordConstraint(): Assert\Sequentially
    {
        return new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Length(min: 8),
            new Assert\Regex('/\d/', 'Password must contain at least one digit.'),
            new Assert\Regex('/[.#&!]/', 'Password must contain at least one allowed special character: . # & !'),
        ]);
    }
}

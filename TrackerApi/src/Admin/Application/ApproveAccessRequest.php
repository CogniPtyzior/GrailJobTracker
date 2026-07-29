<?php

namespace App\Admin\Application;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that approves an access request and provisions the related user.
 */
final class ApproveAccessRequest
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(AccessRequest $accessRequest, array $payload): User
    {
        $normalizedEmail = $this->emailNormalizer->normalize($accessRequest->getEmail());
        $user = $this->userRepository->findOneByNormalizedEmail($normalizedEmail);

        if (!$user instanceof User) {
            $user = new User($accessRequest->getEmail(), $normalizedEmail);
            $this->entityManager->persist($user);
        }

        $user->setFirstName($payload['firstName'] ?? $accessRequest->getFirstName());
        $user->setLastName($payload['lastName'] ?? $accessRequest->getLastName());
        $user->setIsActive(true);
        $user->setRoles(['ROLE_USER']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, (string) $payload['password']));

        $this->entityManager->remove($accessRequest);
        $this->entityManager->flush();

        return $user;
    }
}

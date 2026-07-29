<?php

namespace App\Tests\Integration\Admin\Presentation;

use App\AccessRequest\Application\AccessRequestPresenter;
use App\Admin\Application\ApproveAccessRequest;
use App\Admin\Application\DeleteAccessRequest;
use App\Admin\Presentation\AdminAccessRequestController;
use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Shared\Infrastructure\Validation\PayloadValidator;
use App\Tests\Support\Builder\AccessRequestBuilder;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use App\Tests\Support\Fake\InMemoryUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validation;

final class AdminAccessRequestControllerIntegrationTest extends TestCase
{
    public function testApproveCreatesNewUserAndDeletesAccessRequest(): void
    {
        $accessRequest = AccessRequestBuilder::anAccessRequest()
            ->withEmail('Candidate@example.com')
            ->withFirstName('  Jane ')
            ->withLastName(' Doe  ')
            ->build();
        $accessRequestRepository = new InMemoryAccessRequestRepository([$accessRequest]);
        $userRepository = new InMemoryUserRepository();
        $entityManager = $this->createEntityManager($userRepository, $accessRequestRepository);

        $controller = $this->createController($accessRequestRepository, $userRepository, $entityManager);
        $request = $this->jsonRequest([
            'password' => 'Password1!',
        ]);

        $response = $controller->approve($accessRequest->getId()->toRfc4122(), $request);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($accessRequestRepository->exists($accessRequest));
        self::assertCount(1, $userRepository->all());

        $createdUser = $userRepository->all()[0];
        self::assertSame('Candidate@example.com', $createdUser->getEmail());
        self::assertSame('candidate@example.com', $createdUser->getNormalizedEmail());
        self::assertSame('Jane', $createdUser->getFirstName());
        self::assertSame('Doe', $createdUser->getLastName());
        self::assertSame(['ROLE_USER'], $createdUser->getRoles());
        self::assertTrue($createdUser->isActive());
        self::assertSame('hashed::Password1!', $createdUser->getPassword());
        self::assertSame($createdUser->getId()->toRfc4122(), $payload['item']['id']);
    }

    public function testApproveReusesExistingUserAndPrefersPayloadNames(): void
    {
        $accessRequest = AccessRequestBuilder::anAccessRequest()
            ->withEmail('Candidate@example.com')
            ->withFirstName('RequestFirst')
            ->withLastName('RequestLast')
            ->build();
        $existingUser = UserBuilder::aUser()
            ->withEmail('Candidate@example.com')
            ->withFirstName('Existing')
            ->withLastName('User')
            ->inactive()
            ->build();
        $accessRequestRepository = new InMemoryAccessRequestRepository([$accessRequest]);
        $userRepository = new InMemoryUserRepository([$existingUser]);
        $entityManager = $this->createEntityManager($userRepository, $accessRequestRepository);

        $controller = $this->createController($accessRequestRepository, $userRepository, $entityManager);
        $request = $this->jsonRequest([
            'password' => 'Password1!',
            'firstName' => '  Approved ',
            'lastName' => ' Person  ',
        ]);

        $response = $controller->approve($accessRequest->getId()->toRfc4122(), $request);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertFalse($accessRequestRepository->exists($accessRequest));
        self::assertCount(1, $userRepository->all());
        self::assertSame('Approved', $existingUser->getFirstName());
        self::assertSame('Person', $existingUser->getLastName());
        self::assertTrue($existingUser->isActive());
        self::assertSame(['ROLE_USER'], $existingUser->getRoles());
        self::assertSame('hashed::Password1!', $existingUser->getPassword());
        self::assertSame($existingUser->getId()->toRfc4122(), $payload['item']['id']);
    }

    private function createController(
        InMemoryAccessRequestRepository $accessRequestRepository,
        InMemoryUserRepository $userRepository,
        EntityManagerInterface $entityManager,
    ): AdminAccessRequestController {
        return new AdminAccessRequestController(
            $accessRequestRepository,
            new AccessRequestPresenter(),
            new PayloadValidator(Validation::createValidator()),
            new ApproveAccessRequest(
                $userRepository,
                new EmailNormalizer(),
                $this->passwordHasherStub(),
                $entityManager,
            ),
            new DeleteAccessRequest($entityManager),
        );
    }

    /** @return MockObject&EntityManagerInterface */
    private function createEntityManager(
        InMemoryUserRepository $userRepository,
        InMemoryAccessRequestRepository $accessRequestRepository,
    ): EntityManagerInterface {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('persist')
            ->willReturnCallback(static function (object $entity) use ($userRepository): void {
                if ($entity instanceof User) {
                    $userRepository->save($entity);
                }
            });
        $entityManager->method('remove')
            ->willReturnCallback(static function (object $entity) use ($userRepository, $accessRequestRepository): void {
                if ($entity instanceof User) {
                    $userRepository->remove($entity);
                }

                if ($entity instanceof \App\AccessRequest\Domain\Entity\AccessRequest) {
                    $accessRequestRepository->remove($entity);
                }
            });
        $entityManager->expects(self::once())->method('flush');

        return $entityManager;
    }

    /** @param array<string, mixed> $payload */
    private function jsonRequest(array $payload, string $method = 'POST'): Request
    {
        return Request::create(
            '/',
            $method,
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function passwordHasherStub(): UserPasswordHasherInterface
    {
        return new class implements UserPasswordHasherInterface {
            public function hashPassword(PasswordAuthenticatedUserInterface $user, string $plainPassword): string
            {
                return 'hashed::'.$plainPassword;
            }

            public function isPasswordValid(PasswordAuthenticatedUserInterface $user, string $plainPassword): bool
            {
                return $user->getPassword() === 'hashed::'.$plainPassword;
            }

            public function needsRehash(PasswordAuthenticatedUserInterface $user): bool
            {
                return false;
            }
        };
    }
}
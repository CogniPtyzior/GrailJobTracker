<?php

namespace App\Tests\Integration\Admin\Presentation;

use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\AccessRequest\Application\UseCase\SearchAccessRequests;
use App\AccessRequest\Presentation\AccessRequestPresenter;
use App\Admin\Application\UseCase\ApproveAccessRequest;
use App\Admin\Application\UseCase\DeleteAccessRequest;
use App\Admin\Presentation\AdminAccessRequestController;
use App\Shared\Infrastructure\Validation\RequestPayloadMapper;
use App\Tests\Support\Builder\AccessRequestBuilder;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use App\Tests\Support\Fake\InMemoryUserRepository;
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
        $controller = $this->createController($accessRequestRepository, $userRepository);
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
        self::assertSame('Jane', $createdUser->firstName()?->value());
        self::assertSame('Doe', $createdUser->lastName()?->value());
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
        $controller = $this->createController($accessRequestRepository, $userRepository);
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
        self::assertSame('Approved', $existingUser->firstName()?->value());
        self::assertSame('Person', $existingUser->lastName()?->value());
        self::assertTrue($existingUser->isActive());
        self::assertSame(['ROLE_USER'], $existingUser->getRoles());
        self::assertSame('hashed::Password1!', $existingUser->getPassword());
        self::assertSame($existingUser->getId()->toRfc4122(), $payload['item']['id']);
    }

    private function createController(
        InMemoryAccessRequestRepository $accessRequestRepository,
        InMemoryUserRepository $userRepository,
    ): AdminAccessRequestController {
        return new AdminAccessRequestController(
            new AccessRequestPresenter(),
            new SearchAccessRequests($accessRequestRepository),
            new GetAccessRequest($accessRequestRepository),
            new RequestPayloadMapper(Validation::createValidator()),
            new ApproveAccessRequest(
                $userRepository,
                $accessRequestRepository,
                $this->passwordHasherStub(),
            ),
            new DeleteAccessRequest($accessRequestRepository),
        );
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


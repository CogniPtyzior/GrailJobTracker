<?php

namespace App\Tests\Integration\Admin\Presentation;

use App\Admin\Application\UseCase\CreateAdminUser;
use App\Admin\Application\UseCase\DeleteAdminUser;
use App\Admin\Application\UseCase\GetAdminUser;
use App\Admin\Application\UseCase\SearchUsers;
use App\Admin\Application\UseCase\UpdateAdminUser;
use App\Admin\Presentation\AdminUserController;
use App\Admin\Presentation\UserPresenter;
use App\Security\Application\EmailNormalizer;
use App\Shared\Infrastructure\Validation\RequestPayloadMapper;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Fake\InMemoryUserRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Validation;

final class AdminUserControllerIntegrationTest extends TestCase
{
    public function testCreatePersistsRegularUserAndReturnsCreatedPayload(): void
    {
        $userRepository = new InMemoryUserRepository();
        $controller = $this->createController($userRepository);
        $request = $this->jsonRequest([
            'email' => 'New.User@example.com',
            'password' => 'Password1!',
            'firstName' => '  New ',
            'lastName' => ' User  ',
            'isAdmin' => false,
        ]);

        $response = $controller->create($request);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(201, $response->getStatusCode());
        self::assertCount(1, $userRepository->all());

        $createdUser = $userRepository->all()[0];
        self::assertSame('New.User@example.com', $createdUser->getEmail());
        self::assertSame('new.user@example.com', $createdUser->getNormalizedEmail());
        self::assertSame('New', $createdUser->getFirstName());
        self::assertSame('User', $createdUser->getLastName());
        self::assertSame(['ROLE_USER'], $createdUser->getRoles());
        self::assertSame('hashed::Password1!', $createdUser->getPassword());
        self::assertSame($createdUser->getId()->toRfc4122(), $payload['item']['id']);
    }

    public function testCreateRejectsDuplicateNormalizedEmail(): void
    {
        $existingUser = UserBuilder::aUser()->withEmail('existing@example.com')->build();
        $userRepository = new InMemoryUserRepository([$existingUser]);
        $controller = $this->createController($userRepository);
        $request = $this->jsonRequest([
            'email' => 'Existing@example.com',
            'password' => 'Password1!',
        ]);

        $response = $controller->create($request);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(409, $response->getStatusCode());
        self::assertSame('A user with this email already exists.', $payload['message']);
        self::assertCount(1, $userRepository->all());
    }

    public function testUpdateKeepsBootstrapAdminActiveAndAdminWhenSelfEditing(): void
    {
        $bootstrapAdmin = UserBuilder::aUser()
            ->withEmail('admin@example.com')
            ->withRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->build();
        $userRepository = new InMemoryUserRepository([$bootstrapAdmin]);
        $controller = $this->createController($userRepository, 'admin@example.com');
        $request = $this->jsonRequest([
            'isActive' => false,
            'isAdmin' => false,
            'firstName' => '  Updated ',
        ], 'PUT');

        $response = $controller->update($bootstrapAdmin->getId()->toRfc4122(), $request, $bootstrapAdmin);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($bootstrapAdmin->isActive());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $bootstrapAdmin->getRoles());
        self::assertSame('Updated', $bootstrapAdmin->getFirstName());
        self::assertSame($bootstrapAdmin->getId()->toRfc4122(), $payload['item']['id']);
    }

    public function testUpdateRejectsNullBooleanFlags(): void
    {
        $currentUser = UserBuilder::aUser()->withEmail('admin@example.com')->withRoles(['ROLE_ADMIN', 'ROLE_USER'])->build();
        $managedUser = UserBuilder::aUser()->withEmail('managed@example.com')->withRoles(['ROLE_ADMIN', 'ROLE_USER'])->build();
        $userRepository = new InMemoryUserRepository([$currentUser, $managedUser]);
        $controller = $this->createController($userRepository);
        $request = $this->jsonRequest([
            'isActive' => null,
            'isAdmin' => null,
        ], 'PUT');

        try {
            $controller->update($managedUser->getId()->toRfc4122(), $request, $currentUser);
            self::fail('Expected null boolean flags to be rejected.');
        } catch (BadRequestHttpException $exception) {
            $details = json_decode($exception->getMessage(), true, 512, JSON_THROW_ON_ERROR);
        }

        self::assertContains('[isActive]', array_column($details, 'path'));
        self::assertContains('[isAdmin]', array_column($details, 'path'));
    }

    public function testDeleteRejectsSelfDeletion(): void
    {
        $currentUser = UserBuilder::aUser()->withEmail('self@example.com')->build();
        $userRepository = new InMemoryUserRepository([$currentUser]);
        $controller = $this->createController($userRepository);

        $response = $controller->delete($currentUser->getId()->toRfc4122(), $currentUser);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('The bootstrap admin cannot be deleted.', $payload['message']);
        self::assertCount(1, $userRepository->all());
    }

    public function testDeleteRejectsBootstrapAdminDeletion(): void
    {
        $bootstrapAdmin = UserBuilder::aUser()
            ->withEmail('admin@example.com')
            ->withRoles(['ROLE_ADMIN', 'ROLE_USER'])
            ->build();
        $otherUser = UserBuilder::aUser()->withEmail('other@example.com')->build();
        $userRepository = new InMemoryUserRepository([$bootstrapAdmin, $otherUser]);
        $controller = $this->createController($userRepository, 'admin@example.com');

        $response = $controller->delete($bootstrapAdmin->getId()->toRfc4122(), $otherUser);
        $payload = json_decode($response->getContent() ?: '', true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(400, $response->getStatusCode());
        self::assertSame('The bootstrap admin cannot be deleted.', $payload['message']);
        self::assertCount(2, $userRepository->all());
    }

    private function createController(
        InMemoryUserRepository $userRepository,
        string $adminBootstrapEmail = 'bootstrap@example.com',
    ): AdminUserController {
        $passwordHasher = $this->passwordHasherStub();

        return new AdminUserController(
            new UserPresenter(),
            new SearchUsers($userRepository),
            new GetAdminUser($userRepository),
            new RequestPayloadMapper(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()),
            new CreateAdminUser(new EmailNormalizer(), $userRepository, $passwordHasher),
            new UpdateAdminUser($passwordHasher, $userRepository, $adminBootstrapEmail),
            new DeleteAdminUser($userRepository),
            $adminBootstrapEmail,
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

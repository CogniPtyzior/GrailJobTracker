<?php

namespace App\Tests\Integration\Security\Presentation;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AuthControllerIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'auth-step1-';
    private const PASSWORD = 'Password1!';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepUsers();
        }

        parent::tearDown();
    }

    public function testActiveUserCanLoginAndReadCurrentUser(): void
    {
        $client = $this->createHttpClient();
        $email = self::EMAIL_PREFIX.'active@example.com';

        $this->createUser($email);
        $this->login($client, $email);

        self::assertResponseStatusCodeSame(200);

        $loginPayload = $this->jsonResponse($client);
        self::assertSame($email, $loginPayload['user']['email']);
        self::assertTrue($loginPayload['user']['isActive']);
        self::assertNotNull($loginPayload['user']['lastLoginAt']);

        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(200);

        $mePayload = $this->jsonResponse($client);
        self::assertSame($email, $mePayload['user']['email']);
        self::assertTrue($mePayload['user']['isActive']);
    }

    public function testInactiveUserCannotLoginEvenWithValidPassword(): void
    {
        $client = $this->createHttpClient();
        $email = self::EMAIL_PREFIX.'inactive@example.com';

        $this->createUser($email, isActive: false);
        $this->login($client, $email);

        self::assertResponseStatusCodeSame(401);

        $loginPayload = $this->jsonResponse($client);
        self::assertSame('Invalid credentials.', $loginPayload['message']);
        self::assertSame([], $loginPayload['details']);

        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);

        $mePayload = $this->jsonResponse($client);
        self::assertSame('Authentication required.', $mePayload['message']);
        self::assertSame([], $mePayload['details']);
    }

    public function testExistingSessionIsRejectedAfterUserDeactivation(): void
    {
        $client = $this->createHttpClient();
        $email = self::EMAIL_PREFIX.'deactivated-session@example.com';
        $user = $this->createUser($email);

        $this->login($client, $email);

        self::assertResponseStatusCodeSame(200);

        $managedUser = $this->userRepository()->getById($user->getId());
        $managedUser->deactivate();
        $this->userRepository()->save($managedUser);
        $this->userRepository()->flush();

        $client->request('GET', '/api/auth/me');

        self::assertResponseStatusCodeSame(401);

        $payload = $this->jsonResponse($client);
        self::assertSame('Authentication required.', $payload['message']);
        self::assertSame([], $payload['details']);
    }

    private function createHttpClient(): KernelBrowser
    {
        $client = static::createClient();
        $this->deleteStepUsers();

        return $client;
    }

    private function createUser(string $email, bool $isActive = true): User
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User(EmailAddress::fromString($email));

        $isActive ? $user->activate() : $user->deactivate();
        $user->setPasswordHash($passwordHasher->hashPassword($user, self::PASSWORD));

        $this->userRepository()->save($user);
        $this->userRepository()->flush();

        return $user;
    }

    private function login(KernelBrowser $client, string $email): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '203.0.113.'.random_int(1, 254),
            ],
            content: json_encode([
                'email' => $email,
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function jsonResponse(KernelBrowser $client): array
    {
        $content = $client->getResponse()->getContent();

        self::assertIsString($content);

        $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($payload);

        return $payload;
    }

    private function deleteStepUsers(): void
    {
        $this->connection()
            ->executeStatement('DELETE FROM trackers.users WHERE normalized_email LIKE ?', [self::EMAIL_PREFIX.'%']);
    }

    private function userRepository(): UserRepositoryInterface
    {
        return static::getContainer()->get(UserRepositoryInterface::class);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}


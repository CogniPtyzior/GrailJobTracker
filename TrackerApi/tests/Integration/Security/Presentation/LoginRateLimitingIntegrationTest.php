<?php

namespace App\Tests\Integration\Security\Presentation;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LoginRateLimitingIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'auth-step2-';
    private const PASSWORD = 'Password1!';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepUsers();
        }

        parent::tearDown();
    }

    public function testLoginIsBlockedAfterRepeatedFailedAttempts(): void
    {
        $client = static::createClient();
        $email = self::EMAIL_PREFIX.bin2hex(random_bytes(4)).'@example.com';
        $remoteAddress = $this->randomRemoteAddress();

        $this->deleteStepUsers();
        $this->createUser($email);

        $this->login($client, $email, 'wrong-password', $remoteAddress);
        self::assertResponseStatusCodeSame(401);

        $this->login($client, $email, 'wrong-password', $remoteAddress);
        self::assertResponseStatusCodeSame(401);

        $this->login($client, $email, self::PASSWORD, $remoteAddress);
        self::assertResponseStatusCodeSame(429);

        $payload = $this->jsonResponse($client);
        self::assertSame('Too many login attempts. Please retry later.', $payload['message']);
        self::assertSame([], $payload['details']);
    }

    private function createUser(string $email): void
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = new User(EmailAddress::fromString($email));

        $user->setPasswordHash($passwordHasher->hashPassword($user, self::PASSWORD));

        $userRepository->save($user);
        $userRepository->flush();
    }

    private function login(KernelBrowser $client, string $email, string $password, string $remoteAddress): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => $remoteAddress,
            ],
            content: json_encode([
                'email' => $email,
                'password' => $password,
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

    private function randomRemoteAddress(): string
    {
        return '198.51.100.'.random_int(1, 254);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}


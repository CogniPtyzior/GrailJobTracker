<?php

namespace App\Tests\Integration\TrackedJob\Presentation;

use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TrackedJobPayloadValidationIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'tracked-step5-';
    private const PASSWORD = 'Password1!';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepData();
        }

        parent::tearDown();
    }

    public function testFrontendIsoDateIsAccepted(): void
    {
        $client = static::createClient();
        $user = $this->createUser(self::EMAIL_PREFIX.'valid-date@example.com');

        $this->login($client, $user->getEmail());
        $this->submitTrackedJob($client, [
            'applicationDate' => '2026-07-28T00:00:00.000Z',
        ]);

        self::assertResponseStatusCodeSame(201);

        $payload = $this->jsonResponse($client);

        self::assertSame('2026-07-28T00:00:00+00:00', $payload['item']['applicationDate']);
    }

    public function testInvalidDateIsRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser(self::EMAIL_PREFIX.'invalid-date@example.com');

        $this->login($client, $user->getEmail());
        $this->submitTrackedJob($client, [
            'applicationDate' => '2026-02-31T00:00:00.000Z',
        ]);

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[applicationDate]', $payload);
    }

    public function testTooLongNotesAreRejected(): void
    {
        $client = static::createClient();
        $user = $this->createUser(self::EMAIL_PREFIX.'long-notes@example.com');

        $this->login($client, $user->getEmail());
        $this->submitTrackedJob($client, [
            'notes' => str_repeat('a', 10001),
        ]);

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[notes]', $payload);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function submitTrackedJob(KernelBrowser $client, array $overrides): void
    {
        $client->request(
            'POST',
            '/api/tracked-jobs',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode(array_replace([
                'company' => 'Acme',
                'title' => 'Backend Engineer',
                'contractType' => 'CDI',
                'remoteMode' => null,
                'notes' => null,
                'applicationDate' => null,
            ], $overrides), JSON_THROW_ON_ERROR),
        );
    }

    private function createUser(string $email): User
    {
        $this->deleteStepData();

        $entityManager = $this->entityManager();
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user = new User($email, mb_strtolower(trim($email)));

        $user->setPasswordHash($passwordHasher->hashPassword($user, self::PASSWORD));

        $entityManager->persist($user);
        $entityManager->flush();

        return $user;
    }

    private function login(KernelBrowser $client, string $email): void
    {
        $client->request(
            'POST',
            '/api/auth/login',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode([
                'email' => $email,
                'password' => self::PASSWORD,
            ], JSON_THROW_ON_ERROR),
        );

        self::assertResponseStatusCodeSame(200);
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

    /**
     * @param array<string, mixed> $payload
     */
    private static function assertViolationPath(string $path, array $payload): void
    {
        $paths = array_column($payload['details'], 'path');

        self::assertContains($path, $paths);
    }

    private function deleteStepData(): void
    {
        $connection = $this->entityManager()->getConnection();

        $connection->executeStatement(
            'DELETE FROM trackers.tracked_jobs WHERE owner_id IN (
                SELECT id FROM trackers.users WHERE normalized_email LIKE ?
            )',
            [self::EMAIL_PREFIX.'%'],
        );
        $connection->executeStatement('DELETE FROM trackers.users WHERE normalized_email LIKE ?', [self::EMAIL_PREFIX.'%']);
    }

    private function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }
}

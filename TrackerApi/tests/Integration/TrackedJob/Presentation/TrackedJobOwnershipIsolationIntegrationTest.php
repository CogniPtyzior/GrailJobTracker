<?php

namespace App\Tests\Integration\TrackedJob\Presentation;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Repository\TrackedJobRepositoryInterface;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class TrackedJobOwnershipIsolationIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'tracked-step3-';
    private const PASSWORD = 'Password1!';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepData();
        }

        parent::tearDown();
    }

    public function testUserOnlySeesOwnTrackedJobsInCollectionEndpoints(): void
    {
        $client = static::createClient();
        [$owner, $foreignOwner] = $this->createUsers();

        $ownerJob = $this->createTrackedJob($owner, 'Owner Corp', 'Owner Backend Engineer');
        $foreignJob = $this->createTrackedJob($foreignOwner, 'Foreign Corp', 'Foreign Backend Engineer');

        $this->login($client, $owner->getEmail());

        $client->request('GET', '/api/tracked-jobs');

        self::assertResponseStatusCodeSame(200);

        $payload = $this->jsonResponse($client);
        $jobIds = array_column($payload['items'], 'id');

        self::assertContains($ownerJob->getId()->toRfc4122(), $jobIds);
        self::assertNotContains($foreignJob->getId()->toRfc4122(), $jobIds);

        $client->request('GET', '/api/tracked-jobs/company-suggestions?q=Corp');

        self::assertResponseStatusCodeSame(200);

        $payload = $this->jsonResponse($client);

        self::assertSame(['Owner Corp'], $payload['items']);
    }

    public function testUserCannotAccessAnotherUsersTrackedJob(): void
    {
        $client = static::createClient();
        [$owner, $foreignOwner] = $this->createUsers();
        $foreignJob = $this->createTrackedJob($foreignOwner, 'Foreign Corp', 'Foreign Backend Engineer');

        $this->login($client, $owner->getEmail());

        $client->request('GET', '/api/tracked-jobs/'.$foreignJob->getId()->toRfc4122());

        // Foreign resources return 404 to avoid leaking their existence.
        self::assertResponseStatusCodeSame(404);

        $payload = $this->jsonResponse($client);

        self::assertSame('Tracked job not found.', $payload['message']);
        self::assertSame([], $payload['details']);
    }

    /**
     * @return array{User, User}
     */
    private function createUsers(): array
    {
        $this->deleteStepData();

        return [
            $this->createUser(self::EMAIL_PREFIX.'owner@example.com'),
            $this->createUser(self::EMAIL_PREFIX.'foreign@example.com'),
        ];
    }

    private function createUser(string $email): User
    {
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $userRepository = static::getContainer()->get(UserRepositoryInterface::class);
        $user = new User(EmailAddress::fromString($email));

        $user->setPasswordHash($passwordHasher->hashPassword($user, self::PASSWORD));

        $userRepository->save($user);
        $userRepository->flush();

        return $user;
    }

    private function createTrackedJob(User $owner, string $company, string $title): TrackedJob
    {
        $trackedJobRepository = static::getContainer()->get(TrackedJobRepositoryInterface::class);
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->ownedBy($owner)
            ->withCompany($company)
            ->withTitle($title)
            ->build();

        $trackedJobRepository->save($trackedJob);
        $trackedJobRepository->flush();

        return $trackedJob;
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

    private function deleteStepData(): void
    {
        $connection = $this->connection();

        $connection->executeStatement(
            'DELETE FROM trackers.tracked_jobs WHERE owner_id IN (
                SELECT id FROM trackers.users WHERE normalized_email LIKE ?
            )',
            [self::EMAIL_PREFIX.'%'],
        );
        $connection->executeStatement('DELETE FROM trackers.users WHERE normalized_email LIKE ?', [self::EMAIL_PREFIX.'%']);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}


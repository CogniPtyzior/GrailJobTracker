<?php

namespace App\Tests\Integration\AccessRequest\Presentation;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class PublicAccessRequestAsyncNotificationIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'access-step9-';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepAccessRequests();
        }

        parent::tearDown();
    }

    public function testAccessRequestCreationDispatchesAsyncNotification(): void
    {
        $client = static::createClient();
        $email = self::EMAIL_PREFIX.random_int(1000, 9999).'@example.com';

        $this->deleteStepAccessRequests();
        $this->asyncTransport()->reset();

        $this->submitAccessRequest($client, $email);

        self::assertResponseStatusCodeSame(201);

        $accessRequestId = $this->findStepAccessRequestId($email);
        $sentMessages = $this->asyncTransport()->getSent();

        self::assertNotNull($accessRequestId);
        self::assertCount(1, $sentMessages);
        self::assertInstanceOf(SendAccessRequestNotification::class, $sentMessages[0]->getMessage());
        self::assertSame($accessRequestId, $sentMessages[0]->getMessage()->accessRequestId);
    }

    private function submitAccessRequest(KernelBrowser $client, string $email): void
    {
        $client->request(
            'POST',
            '/api/access-requests',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '198.51.100.9',
            ],
            content: json_encode([
                'email' => $email,
                'companyName' => 'Acme',
                'reason' => 'I would like to try the tracker for my job search.',
            ], JSON_THROW_ON_ERROR),
        );
    }

    private function findStepAccessRequestId(string $email): ?string
    {
        $id = $this->connection()->fetchOne(
            'SELECT id FROM trackers.access_requests WHERE normalized_email = ?',
            [mb_strtolower($email)],
        );

        return is_string($id) ? $id : null;
    }

    private function deleteStepAccessRequests(): void
    {
        $this->connection()->executeStatement(
            'DELETE FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [self::EMAIL_PREFIX.'%'],
        );
    }

    private function asyncTransport(): InMemoryTransport
    {
        $transport = static::getContainer()->get('messenger.transport.async');

        self::assertInstanceOf(InMemoryTransport::class, $transport);

        return $transport;
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}

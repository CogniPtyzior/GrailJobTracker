<?php

namespace App\Tests\Integration\AccessRequest\Presentation;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicAccessRequestRateLimitingIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'access-step2-';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepAccessRequests();
        }

        parent::tearDown();
    }

    public function testAccessRequestsAreRejectedAfterLimit(): void
    {
        $client = static::createClient();
        $remoteAddress = $this->randomRemoteAddress();

        $this->deleteStepAccessRequests();

        $this->submitAccessRequest($client, self::EMAIL_PREFIX.'allowed-1@example.com', $remoteAddress);
        self::assertResponseStatusCodeSame(201);

        $this->submitAccessRequest($client, self::EMAIL_PREFIX.'allowed-2@example.com', $remoteAddress);
        self::assertResponseStatusCodeSame(201);

        self::assertSame(2, $this->countStepAccessRequests());

        $this->submitAccessRequest($client, self::EMAIL_PREFIX.'blocked@example.com', $remoteAddress);
        self::assertResponseStatusCodeSame(429);

        $payload = $this->jsonResponse($client);
        self::assertSame('Too many access request submissions. Please retry later.', $payload['message']);
        self::assertSame([], $payload['details']);
        self::assertSame(2, $this->countStepAccessRequests());
    }

    private function submitAccessRequest(KernelBrowser $client, string $email, string $remoteAddress): void
    {
        $client->request(
            'POST',
            '/api/access-requests',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => $remoteAddress,
            ],
            content: json_encode([
                'email' => $email,
                'companyName' => 'Acme',
                'reason' => 'I would like to try the tracker for my job search.',
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

    private function countStepAccessRequests(): int
    {
        return (int) $this->connection()->fetchOne(
            'SELECT COUNT(*) FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [self::EMAIL_PREFIX.'%'],
        );
    }

    private function deleteStepAccessRequests(): void
    {
        $this->connection()->executeStatement(
            'DELETE FROM trackers.access_requests WHERE normalized_email LIKE ?',
            [self::EMAIL_PREFIX.'%'],
        );
    }

    private function randomRemoteAddress(): string
    {
        return '203.0.113.'.random_int(1, 254);
    }

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}
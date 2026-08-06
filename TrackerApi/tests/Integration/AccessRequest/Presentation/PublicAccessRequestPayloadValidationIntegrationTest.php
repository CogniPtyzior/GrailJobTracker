<?php

namespace App\Tests\Integration\AccessRequest\Presentation;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PublicAccessRequestPayloadValidationIntegrationTest extends WebTestCase
{
    private const EMAIL_PREFIX = 'access-step5-';

    protected function tearDown(): void
    {
        if (self::$booted) {
            $this->deleteStepAccessRequests();
        }

        parent::tearDown();
    }

    public function testTooLongReasonIsRejected(): void
    {
        $client = static::createClient();

        $this->deleteStepAccessRequests();
        $this->submitAccessRequest($client, str_repeat('a', 5001));

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[reason]', $payload);
        self::assertSame(0, $this->countStepAccessRequests());
    }

    public function testTooShortReasonIsRejected(): void
    {
        $client = static::createClient();

        $this->deleteStepAccessRequests();
        $this->submitAccessRequest($client, 'Too short');

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[reason]', $payload);
        self::assertSame(0, $this->countStepAccessRequests());
    }

    public function testBlankReasonIsRejectedBeforeDomainConstruction(): void
    {
        $client = static::createClient();

        $this->deleteStepAccessRequests();
        $this->submitAccessRequest($client, '   ');

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[reason]', $payload);
        self::assertSame(0, $this->countStepAccessRequests());
    }

    public function testBlankCompanyNameIsRejectedBeforeDomainConstruction(): void
    {
        $client = static::createClient();

        $this->deleteStepAccessRequests();
        $this->submitAccessRequest($client, 'I would like to try the tracker.', companyName: '   ');

        self::assertResponseStatusCodeSame(400);

        $payload = $this->jsonResponse($client);

        self::assertSame('Invalid request payload.', $payload['message']);
        self::assertViolationPath('[companyName]', $payload);
        self::assertSame(0, $this->countStepAccessRequests());
    }

    private function submitAccessRequest(KernelBrowser $client, string $reason, string $companyName = 'Acme'): void
    {
        $client->request(
            'POST',
            '/api/access-requests',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'REMOTE_ADDR' => '203.0.113.'.random_int(1, 254),
            ],
            content: json_encode([
                'email' => self::EMAIL_PREFIX.'validation@example.com',
                'companyName' => $companyName,
                'reason' => $reason,
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

    /**
     * @param array<string, mixed> $payload
     */
    private static function assertViolationPath(string $path, array $payload): void
    {
        $paths = array_column($payload['details'], 'path');

        self::assertContains($path, $paths);
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

    private function connection(): Connection
    {
        return static::getContainer()->get(Connection::class);
    }
}
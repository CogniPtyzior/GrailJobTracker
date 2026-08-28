<?php

declare(strict_types=1);

/*
 * Unit tests for the public access request API processor.
 * They verify orchestration between throttling, input mapping, application use case and compatible empty JSON output.
 */

use ApiPlatform\Metadata\Post;
use App\AccessRequest\Api\Input\CreateAccessRequestInput;
use App\AccessRequest\Api\Mapper\AccessRequestInputMapper;
use App\AccessRequest\Api\Processor\CreateAccessRequestProcessor;
use App\AccessRequest\Api\RateLimit\AccessRequestSubmissionLimiterInterface;
use App\AccessRequest\Application\UseCase\CreateAccessRequest;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\Shared\Application\Metrics\MetricsInterface;
use App\Tests\Support\Fake\FakeAccessRequestNotificationDispatcher;
use App\Tests\Support\Fake\InMemoryTransactionManager;

it('creates an access request and returns the frontend-compatible empty JSON response', function (): void {
    $repository = processorAccessRequestRepository();
    $processor = new CreateAccessRequestProcessor(
        processorAccessRequestLimiter(),
        new AccessRequestInputMapper(),
        new CreateAccessRequest($repository, new FakeAccessRequestNotificationDispatcher(), new InMemoryTransactionManager(), $this->createStub(MetricsInterface::class)),
    );
    $input = new CreateAccessRequestInput();
    $input->email = 'p19-processor@example.com';
    $input->companyName = 'Acme';
    $input->reason = 'This request should be created through the API processor.';

    $response = $processor->process($input, new Post());

    expect($response->getStatusCode())->toBe(201)
        ->and($response->getContent())->toBe('[]')
        ->and($repository->saved)->toHaveCount(1);
});

it('rejects invalid processor payload objects', function (): void {
    $processor = new CreateAccessRequestProcessor(
        processorAccessRequestLimiter(),
        new AccessRequestInputMapper(),
        new CreateAccessRequest(
            processorAccessRequestRepository(),
            new FakeAccessRequestNotificationDispatcher(),
            new InMemoryTransactionManager(),
            $this->createStub(MetricsInterface::class),
        ),
    );

    expect(fn () => $processor->process(new stdClass(), new Post()))
        ->toThrow(InvalidApplicationCommand::class, 'Invalid access request create payload.');
});

function processorAccessRequestLimiter(): AccessRequestSubmissionLimiterInterface
{
    return new class implements AccessRequestSubmissionLimiterInterface {
        public function enforce(): void
        {
        }
    };
    };

function processorAccessRequestRepository(): AccessRequestRepositoryInterface
{
    return new class implements AccessRequestRepositoryInterface {
        /** @var list<AccessRequest> */
        public array $saved = [];

        public function getById(AccessRequestId $id): ?AccessRequest
        {
            foreach ($this->saved as $accessRequest) {
                if ($accessRequest->getId()->equals($id)) {
                    return $accessRequest;
                }
            }

            return null;
        }

        public function search(?string $query, int $page, int $pageSize): array
        {
            return ['items' => $this->saved, 'total' => count($this->saved)];
        }

        public function save(AccessRequest $accessRequest): void
        {
            $this->saved[] = $accessRequest;
        }

        public function remove(AccessRequest $accessRequest): void
        {
        }

    };
}

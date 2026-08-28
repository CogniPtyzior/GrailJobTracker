<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job write processors.
 * They verify API Platform processors delegate to use cases and enforce object authorization before mutations.
 */

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryTrackedJobRepository;
use App\Tests\Support\Fake\InMemoryTransactionManager;
use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Input\ExportTrackedJobsInput;
use App\TrackedJob\Api\Input\UpdateTrackedJobInput;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Mapper\TrackedJobExportInputMapper;
use App\TrackedJob\Api\Mapper\TrackedJobInputMapper;
use App\TrackedJob\Api\Processor\CreateTrackedJobProcessor;
use App\TrackedJob\Api\Processor\DeleteTrackedJobProcessor;
use App\TrackedJob\Api\Processor\ExportTrackedJobsCsvProcessor;
use App\TrackedJob\Api\Processor\UpdateTrackedJobProcessor;
use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Application\UseCase\CreateTrackedJob;
use App\TrackedJob\Application\UseCase\ExportTrackedJobsCsv;
use App\TrackedJob\Application\UseCase\DeleteTrackedJob;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Application\UseCase\UpdateTrackedJob;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

it('creates a tracked job and returns the API item output', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $input = new CreateTrackedJobInput();
    $input->company = 'Acme';
    $input->title = 'Backend Engineer';

    $output = (new CreateTrackedJobProcessor(
        $resolver,
        new TrackedJobInputMapper(),
        new CreateTrackedJob(new TrackedJobFactory(), new TrackedJobCommandApplier(), $repository, $transactionManager),
        new TrackedJobApiMapper(),
    ))->process($input, new Post());

    expect($output->item->company)->toBe('Acme')
        ->and($output->item->title)->toBe('Backend Engineer')
        ->and($repository->saveCalls)->toBe(1)
        ->and($transactionManager->transactionCalls)->toBe(1)
        ->and($repository->getByIdForOwner(TrackedJobId::fromString($output->item->id), $owner->getId()))
        ->toBeInstanceOf(TrackedJob::class);
});


it('exports tracked jobs as a CSV file response', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $input = new ExportTrackedJobsInput();
    $input->company = ' Acme ';

    $response = (new ExportTrackedJobsCsvProcessor(
        $resolver,
        new TrackedJobExportInputMapper(),
        new ExportTrackedJobsCsv($repository, new TrackedJobCsvExporter()),
    ))->process($input, new Post());

    expect($response->getStatusCode())->toBe(200)
        ->and($response->headers->get('Content-Type'))->toContain('text/csv')
        ->and($response->headers->get('Content-Disposition'))->toBe('attachment; filename="tracked-jobs.csv"')
        ->and($response->getContent())->toContain($trackedJob->getId()->toRfc4122())
        ->and($repository->lastSearch['filters']['company'])->toBe('Acme');
});
it('updates an owner-scoped tracked job when the voter grants access', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->saveCalls = 0;
    $input = new UpdateTrackedJobInput();
    $input->company = 'Updated';

    $output = (new UpdateTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new TrackedJobInputMapper(),
        new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository, $transactionManager),
        new TrackedJobApiMapper(),
        trackedJobWriteAuthorization(),
    ))->process($input, new Put(), ['id' => $trackedJob->getId()->toRfc4122()]);

    expect($output->item->id)->toBe($trackedJob->getId()->toRfc4122())
        ->and($output->item->company)->toBe('Updated')
        ->and($repository->saveCalls)->toBe(1)
        ->and($transactionManager->transactionCalls)->toBe(1);
});

it('does not update a loaded tracked job when the voter denies access', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->saveCalls = 0;
    $input = new UpdateTrackedJobInput();
    $input->company = 'Denied';

    (new UpdateTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new TrackedJobInputMapper(),
        new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository, $transactionManager),
        new TrackedJobApiMapper(),
        trackedJobWriteAuthorization(false),
    ))->process($input, new Put(), ['id' => $trackedJob->getId()->toRfc4122()]);
})->throws(AccessDeniedException::class, 'Access denied.');

it('deletes an owner-scoped tracked job when the voter grants access', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->removeCalls = 0;

    $result = (new DeleteTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new DeleteTrackedJob($repository, $transactionManager),
        trackedJobWriteAuthorization(),
    ))->process(null, new Delete(), ['id' => $trackedJob->getId()->toRfc4122()]);

    expect($result)->toBeNull()
        ->and($repository->removeCalls)->toBe(1)
        ->and($transactionManager->transactionCalls)->toBe(1)
        ->and($repository->getByIdForOwner($trackedJob->getId(), $owner->getId()))->toBeNull();
});

it('does not delete a loaded tracked job when the voter denies access', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);

    (new DeleteTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new DeleteTrackedJob($repository, $transactionManager),
        trackedJobWriteAuthorization(false),
    ))->process(null, new Delete(), ['id' => $trackedJob->getId()->toRfc4122()]);
})->throws(AccessDeniedException::class, 'Access denied.');

it('returns not found for update when the tracked job does not belong to the user', function (): void {
    [, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $transactionManager = new InMemoryTransactionManager();
    $foreignOwner = new User(EmailAddress::fromString('foreign@example.com'));
    $trackedJob = TrackedJob::openFor($foreignOwner->getId());
    $repository->save($trackedJob);
    $input = new UpdateTrackedJobInput();
    $input->company = 'Forbidden';

    (new UpdateTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new TrackedJobInputMapper(),
        new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository, $transactionManager),
        new TrackedJobApiMapper(),
        trackedJobWriteAuthorization(),
    ))->process($input, new Put(), ['id' => $trackedJob->getId()->toRfc4122()]);
})->throws(ApplicationNotFound::class, 'Tracked job not found.');

/** @return array{0: User, 1: AuthenticatedUserResolver} */
function writeProcessorUserContext(): array
{
    $user = new User(EmailAddress::fromString('writer@example.com'));
    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($user), 'main', ['ROLE_USER']));

    return [$user, new AuthenticatedUserResolver($tokenStorage)];
}

function trackedJobWriteAuthorization(bool $granted = true): AuthorizationCheckerInterface
{
    return new class($granted) implements AuthorizationCheckerInterface {
        public function __construct(private readonly bool $granted)
        {
        }

        public function isGranted(mixed $attribute, mixed $subject = null): bool
        {
            return $this->granted;
        }
    };
}

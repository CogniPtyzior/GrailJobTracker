<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job write processors.
 * They verify API Platform processors delegate to application use cases and keep owner-scoped loading.
 */

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Tests\Support\Fake\InMemoryTrackedJobRepository;
use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Input\UpdateTrackedJobInput;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Mapper\TrackedJobInputMapper;
use App\TrackedJob\Api\Processor\CreateTrackedJobProcessor;
use App\TrackedJob\Api\Processor\DeleteTrackedJobProcessor;
use App\TrackedJob\Api\Processor\UpdateTrackedJobProcessor;
use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Application\UseCase\CreateTrackedJob;
use App\TrackedJob\Application\UseCase\DeleteTrackedJob;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Application\UseCase\UpdateTrackedJob;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

it('creates a tracked job and returns the API item output', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $input = new CreateTrackedJobInput();
    $input->company = 'Acme';
    $input->title = 'Backend Engineer';

    $output = (new CreateTrackedJobProcessor(
        $resolver,
        new TrackedJobInputMapper(),
        new CreateTrackedJob(new TrackedJobFactory(), new TrackedJobCommandApplier(), $repository),
        new TrackedJobApiMapper(),
    ))->process($input, new Post());

    expect($output->item->company)->toBe('Acme')
        ->and($output->item->title)->toBe('Backend Engineer')
        ->and($repository->saveCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1)
        ->and($repository->getByIdForOwner(
            \App\TrackedJob\Domain\ValueObject\TrackedJobId::fromString($output->item->id),
            $owner->getId(),
        ))->toBeInstanceOf(TrackedJob::class);
});

it('updates an owner-scoped tracked job and returns the API item output', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->saveCalls = 0;
    $repository->flushCalls = 0;
    $input = new UpdateTrackedJobInput();
    $input->company = 'Updated';

    $output = (new UpdateTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new TrackedJobInputMapper(),
        new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository),
        new TrackedJobApiMapper(),
    ))->process($input, new Put(), ['id' => $trackedJob->getId()->toRfc4122()]);

    expect($output->item->id)->toBe($trackedJob->getId()->toRfc4122())
        ->and($output->item->company)->toBe('Updated')
        ->and($repository->saveCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1);
});

it('deletes an owner-scoped tracked job', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor($owner->getId());
    $repository->save($trackedJob);
    $repository->removeCalls = 0;
    $repository->flushCalls = 0;

    $result = (new DeleteTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new DeleteTrackedJob($repository),
    ))->process(null, new Delete(), ['id' => $trackedJob->getId()->toRfc4122()]);

    expect($result)->toBeNull()
        ->and($repository->removeCalls)->toBe(1)
        ->and($repository->flushCalls)->toBe(1)
        ->and($repository->getByIdForOwner($trackedJob->getId(), $owner->getId()))->toBeNull();
});

it('returns not found for update when the tracked job does not belong to the user', function (): void {
    [$owner, $resolver] = writeProcessorUserContext();
    $repository = new InMemoryTrackedJobRepository();
    $foreignOwner = new User(EmailAddress::fromString('foreign@example.com'));
    $trackedJob = TrackedJob::openFor($foreignOwner->getId());
    $repository->save($trackedJob);
    $input = new UpdateTrackedJobInput();
    $input->company = 'Forbidden';

    (new UpdateTrackedJobProcessor(
        $resolver,
        new GetTrackedJob($repository),
        new TrackedJobInputMapper(),
        new UpdateTrackedJob(new TrackedJobCommandApplier(), $repository),
        new TrackedJobApiMapper(),
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
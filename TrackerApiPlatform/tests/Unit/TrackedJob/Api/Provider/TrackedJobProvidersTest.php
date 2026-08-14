<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job API providers.
 * They verify query parsing, owner scoping and item not-found behavior without HTTP or Doctrine.
 */

use ApiPlatform\Metadata\Get;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Tests\Support\Fake\InMemoryTrackedJobRepository;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Provider\TrackedJobCollectionProvider;
use App\TrackedJob\Api\Provider\TrackedJobItemProvider;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Application\UseCase\SearchTrackedJobs;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

it('provides an owner-filtered tracked job collection from query parameters', function (): void {
    [$user, $tokenStorage] = trackedJobProviderUser();
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor($user->getId());
    $repository->save($trackedJob);
    $repository->hasMore = true;
    $requestStack = new RequestStack();
    $requestStack->push(Request::create('/api/tracked-jobs', 'GET', [
        'page' => '2',
        'pageSize' => '25',
        'company' => 'acme',
        'contractType' => 'CDD',
        'status' => 'INVALID',
    ]));

    $provider = new TrackedJobCollectionProvider(
        new AuthenticatedUserResolver($tokenStorage),
        new SearchTrackedJobs($repository),
        new TrackedJobApiMapper(),
        $requestStack,
    );

    $output = $provider->provide(new Get());

    expect($output->items)->toHaveCount(1)
        ->and($output->page)->toBe(2)
        ->and($output->pageSize)->toBe(25)
        ->and($output->hasMore)->toBeTrue()
        ->and($repository->lastSearch['ownerId'])->toBe($user->getId()->toRfc4122())
        ->and($repository->lastSearch['filters']['company'])->toBe('acme')
        ->and($repository->lastSearch['filters']['contractType'])->toBe(ContractType::CDD)
        ->and($repository->lastSearch['filters']['statusInvalid'])->toBeTrue();
});

it('provides a tracked job item for the authenticated owner', function (): void {
    [$user, $tokenStorage] = trackedJobProviderUser();
    $repository = new InMemoryTrackedJobRepository();
    $trackedJob = TrackedJob::openFor($user->getId());
    $repository->save($trackedJob);
    $provider = new TrackedJobItemProvider(
        new AuthenticatedUserResolver($tokenStorage),
        new GetTrackedJob($repository),
        new TrackedJobApiMapper(),
    );

    $output = $provider->provide(new Get(), ['id' => $trackedJob->getId()->toRfc4122()]);

    expect($output->item->id)->toBe($trackedJob->getId()->toRfc4122());
});

it('throws an application not found exception for unknown item ids', function (): void {
    [, $tokenStorage] = trackedJobProviderUser();
    $provider = new TrackedJobItemProvider(
        new AuthenticatedUserResolver($tokenStorage),
        new GetTrackedJob(new InMemoryTrackedJobRepository()),
        new TrackedJobApiMapper(),
    );

    $provider->provide(new Get(), ['id' => 'not-a-uuid']);
})->throws(ApplicationNotFound::class, 'Tracked job not found.');

/** @return array{0: User, 1: TokenStorage} */
function trackedJobProviderUser(): array
{
    $user = new User(EmailAddress::fromString('reader@example.com'));
    $tokenStorage = new TokenStorage();
    $tokenStorage->setToken(new UsernamePasswordToken(new SecurityUser($user), 'main', ['ROLE_USER']));

    return [$user, $tokenStorage];
}

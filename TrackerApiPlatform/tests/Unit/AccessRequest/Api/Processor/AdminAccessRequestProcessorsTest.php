<?php

declare(strict_types=1);

/*
 * Unit tests for admin access request API processors.
 * They verify API orchestration and not-found mapping without running Symfony HTTP.
 */

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Post;
use App\AccessRequest\Api\Input\ApproveAccessRequestInput as ApiApproveAccessRequestInput;
use App\AccessRequest\Api\Mapper\AccessRequestApiMapper;
use App\AccessRequest\Api\Processor\ApproveAccessRequestProcessor;
use App\AccessRequest\Api\Processor\DeleteAccessRequestProcessor;
use App\AccessRequest\Application\UseCase\ApproveAccessRequest;
use App\AccessRequest\Application\UseCase\DeleteAccessRequest;
use App\AccessRequest\Application\UseCase\GetAccessRequest;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use App\Tests\Support\Fake\InMemoryUserRepository;

it('approves an access request and returns the created user envelope', function (): void {
    $accessRequest = adminProcessorAccessRequest('processor@example.com');
    $accessRequests = new InMemoryAccessRequestRepository([$accessRequest]);
    $users = new InMemoryUserRepository();
    $processor = new ApproveAccessRequestProcessor(
        new GetAccessRequest($accessRequests),
        new ApproveAccessRequest($users, $accessRequests, adminProcessorPasswordHasher()),
        new AccessRequestApiMapper(),
    );
    $input = new ApiApproveAccessRequestInput();
    $input->password = 'Password1!';

    $output = $processor->process($input, new Post(), ['id' => $accessRequest->getId()->toRfc4122()]);

    expect($output->item->email)->toBe('processor@example.com')
        ->and($accessRequests->getById($accessRequest->getId()))->toBeNull();
});

it('deletes an access request by id', function (): void {
    $accessRequest = adminProcessorAccessRequest('processor-delete@example.com');
    $accessRequests = new InMemoryAccessRequestRepository([$accessRequest]);
    $processor = new DeleteAccessRequestProcessor(
        new GetAccessRequest($accessRequests),
        new DeleteAccessRequest($accessRequests),
    );

    $processor->process(null, new Delete(), ['id' => $accessRequest->getId()->toRfc4122()]);

    expect($accessRequests->getById($accessRequest->getId()))->toBeNull();
});

it('returns application not found for invalid admin access request ids', function (): void {
    $accessRequests = new InMemoryAccessRequestRepository();
    $processor = new DeleteAccessRequestProcessor(
        new GetAccessRequest($accessRequests),
        new DeleteAccessRequest($accessRequests),
    );

    expect(fn () => $processor->process(null, new Delete(), ['id' => 'invalid']))
        ->toThrow(ApplicationNotFound::class, 'Access request not found.');
});

function adminProcessorAccessRequest(string $email): \App\AccessRequest\Domain\Entity\AccessRequest
{
    return \App\AccessRequest\Domain\Entity\AccessRequest::submit(
        \App\Shared\Domain\ValueObject\EmailAddress::fromString($email),
        \App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName::fromString('Acme'),
        \App\AccessRequest\Domain\ValueObject\AccessRequestReason::fromString('This request should be processed by admin endpoints.'),
        null,
        null,
    );
}

function adminProcessorPasswordHasher(): \App\Security\Application\Security\PasswordHasherInterface
{
    return new class implements \App\Security\Application\Security\PasswordHasherInterface {
        public function hash(\App\Security\Domain\Entity\User $user, string $plainPassword): string
        {
            return 'hashed::'.$plainPassword;
        }
    };
}

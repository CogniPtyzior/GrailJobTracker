<?php

declare(strict_types=1);

/*
 * Unit tests for access request admin API output mapping.
 * They protect the legacy frontend envelopes without exposing domain objects directly.
 */

use App\AccessRequest\Api\Mapper\AccessRequestApiMapper;
use App\AccessRequest\Application\Result\SearchAccessRequestsResult;
use App\Security\Domain\Entity\User;
use App\Shared\Domain\ValueObject\EmailAddress;

it('maps access request search results to the admin collection envelope', function (): void {
    $accessRequest = adminMapperAccessRequest('list@example.com', 'Jane', 'Doe');
    $output = (new AccessRequestApiMapper())->toCollectionOutput(
        new SearchAccessRequestsResult([$accessRequest], 1),
        2,
        25,
    );

    expect($output->items)->toHaveCount(1)
        ->and($output->items[0]->email)->toBe('list@example.com')
        ->and($output->items[0]->companyName)->toBe('Acme')
        ->and($output->items[0]->firstName)->toBe('Jane')
        ->and($output->items[0]->lastName)->toBe('Doe')
        ->and($output->page)->toBe(2)
        ->and($output->pageSize)->toBe(25)
        ->and($output->total)->toBe(1);
});

it('maps approved users to the legacy item envelope', function (): void {
    $user = new User(EmailAddress::fromString('approved@example.com'));

    $output = (new AccessRequestApiMapper())->toApprovedOutput($user);

    expect($output->item->id)->toBe($user->getId()->toRfc4122())
        ->and($output->item->email)->toBe('approved@example.com');
});

function adminMapperAccessRequest(
    string $email,
    ?string $firstName = null,
    ?string $lastName = null,
): \App\AccessRequest\Domain\Entity\AccessRequest
{
    return \App\AccessRequest\Domain\Entity\AccessRequest::submit(
        \App\Shared\Domain\ValueObject\EmailAddress::fromString($email),
        \App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName::fromString('Acme'),
        \App\AccessRequest\Domain\ValueObject\AccessRequestReason::fromString('This request should be mapped for admin output.'),
        \App\Shared\Domain\ValueObject\PersonName::fromNullable($firstName),
        \App\Shared\Domain\ValueObject\PersonName::fromNullable($lastName),
    );
}

<?php

namespace App\Tests\Unit\AccessRequest\Presentation;

use App\AccessRequest\Presentation\AccessRequestPresenter;
use App\Tests\Support\Builder\AccessRequestBuilder;
use PHPUnit\Framework\TestCase;

final class AccessRequestPresenterTest extends TestCase
{
    public function testPresentMapsAccessRequestToArray(): void
    {
        $accessRequest = AccessRequestBuilder::anAccessRequest()->build();

        $presented = (new AccessRequestPresenter())->present($accessRequest);

        self::assertSame($accessRequest->getId()->toRfc4122(), $presented['id']);
        self::assertSame('john.doe@example.com', $presented['email']);
        self::assertSame('Acme', $presented['companyName']);
        self::assertSame('Need access to manage tracked jobs.', $presented['reason']);
        self::assertSame('John', $presented['firstName']);
        self::assertSame('Doe', $presented['lastName']);
        self::assertSame($accessRequest->getCreatedAt()->format(\DateTimeInterface::ATOM), $presented['createdAt']);
    }
}

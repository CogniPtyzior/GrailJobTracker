<?php

declare(strict_types=1);

/*
 * Unit tests for the public access request API input mapper.
 * They ensure validated API DTOs are translated into application inputs without leaking API Platform downstream.
 */

use App\AccessRequest\Api\Input\CreateAccessRequestInput;
use App\AccessRequest\Api\Mapper\AccessRequestInputMapper;

it('maps API input to the access request application input', function (): void {
    $input = new CreateAccessRequestInput();
    $input->email = 'Applicant@Example.com';
    $input->companyName = '  Acme  ';
    $input->reason = '  I would like access to manage my tracked job applications.  ';
    $input->firstName = '  Jane  ';
    $input->lastName = '  Doe  ';

    $mapped = (new AccessRequestInputMapper())->toCreateInput($input);

    expect($mapped->email)->toBe('Applicant@Example.com')
        ->and($mapped->companyName->value())->toBe('Acme')
        ->and($mapped->reason->value())->toBe('I would like access to manage my tracked job applications.')
        ->and($mapped->firstName?->value())->toBe('Jane')
        ->and($mapped->lastName?->value())->toBe('Doe');
});

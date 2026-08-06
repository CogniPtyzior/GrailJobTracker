<?php

namespace App\Tests\Support\Payload;

final class TrackedJobPayloads
{
    /** @return array<string, mixed> */
    public static function minimal(): array
    {
        return [
            'company' => 'Acme',
            'title' => 'Backend Engineer',
            'applicationDate' => '2026-04-01T09:00:00+00:00',
        ];
    }

    /** @return array<string, mixed> */
    public static function full(): array
    {
        return [
            'company' => ' Acme ',
            'title' => ' Backend Engineer ',
            'contractType' => 'CDD',
            'location' => ' Paris ',
            'remoteMode' => 'FULL',
            'remuneration' => ' 60k ',
            'offerUrl' => ' https://example.com/job ',
            'notes' => ' Strong fit ',
            'applicationDate' => '2026-04-01T09:00:00+00:00',
            'effectiveFollowUpDate' => '2026-04-10T09:00:00+00:00',
            'firstContactDate' => '2026-04-11T09:00:00+00:00',
            'preliminaryInterviewDate' => '2026-04-15T09:00:00+00:00',
            'secondInterviewDate' => '2026-04-20T09:00:00+00:00',
            'hrContactName' => ' Jane HR ',
            'businessContactName' => ' Bob Manager ',
            'subjectiveRelevance' => '9',
        ];
    }

    /** @return array<string, mixed> */
    public static function withInvalidEnums(): array
    {
        return [
            'company' => '   ',
            'title' => '   ',
            'contractType' => 'INVALID',
            'remoteMode' => 'INVALID',
            'location' => '   ',
            'remuneration' => '   ',
            'offerUrl' => '   ',
            'notes' => '   ',
            'hrContactName' => '   ',
            'businessContactName' => '   ',
            'subjectiveRelevance' => '',
        ];
    }
}

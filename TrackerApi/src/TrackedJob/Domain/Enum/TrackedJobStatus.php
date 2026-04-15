<?php

namespace App\TrackedJob\Domain\Enum;

enum TrackedJobStatus: string
{
    case DRAFT = 'DRAFT';
    case APPLIED = 'APPLIED';
    case FOLLOW_UP_PENDING = 'FOLLOW_UP_PENDING';
    case FOLLOW_UP_DONE = 'FOLLOW_UP_DONE';
    case FIRST_CONTACT = 'FIRST_CONTACT';
    case PRELIMINARY_INTERVIEW = 'PRELIMINARY_INTERVIEW';
    case SECOND_INTERVIEW = 'SECOND_INTERVIEW';
    case OFFER_RECEIVED = 'OFFER_RECEIVED';
    case HIRED = 'HIRED';
    case REJECTED = 'REJECTED';
    case WITHDRAWN = 'WITHDRAWN';

    public function isFinal(): bool
    {
        return in_array($this, [self::OFFER_RECEIVED, self::HIRED, self::REJECTED, self::WITHDRAWN], true);
    }
}

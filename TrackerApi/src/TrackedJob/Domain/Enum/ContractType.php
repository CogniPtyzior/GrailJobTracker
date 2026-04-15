<?php

namespace App\TrackedJob\Domain\Enum;

enum ContractType: string
{
    case CDI = 'CDI';
    case CDD = 'CDD';
    case FREELANCE = 'FREELANCE';
    case INTERNSHIP = 'INTERNSHIP';
    case APPRENTICESHIP = 'APPRENTICESHIP';
    case OTHER = 'OTHER';
}

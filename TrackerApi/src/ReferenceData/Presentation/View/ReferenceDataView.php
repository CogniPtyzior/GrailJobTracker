<?php

namespace App\ReferenceData\Presentation\View;

final readonly class ReferenceDataView
{
    /**
     * @param list<string> $contractTypes
     * @param list<string> $remoteModes
     * @param list<string> $trackedJobStatuses
     */
    public function __construct(
        public array $contractTypes,
        public array $remoteModes,
        public array $trackedJobStatuses,
        public string $defaultContractType,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'contractTypes' => $this->contractTypes,
            'remoteModes' => $this->remoteModes,
            'trackedJobStatuses' => $this->trackedJobStatuses,
            'defaultContractType' => $this->defaultContractType,
        ];
    }
}
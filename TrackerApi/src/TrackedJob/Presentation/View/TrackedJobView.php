<?php

namespace App\TrackedJob\Presentation\View;

final readonly class TrackedJobView
{
    public function __construct(
        public string $id,
        public ?string $company,
        public ?string $title,
        public ?string $contractType,
        public ?string $location,
        public ?string $remoteMode,
        public ?string $remuneration,
        public ?string $offerUrl,
        public ?string $notes,
        public ?string $applicationDate,
        public ?string $plannedFollowUpDate,
        public ?string $effectiveFollowUpDate,
        public ?string $firstContactDate,
        public ?string $preliminaryInterviewDate,
        public ?string $secondInterviewDate,
        public ?string $hrContactName,
        public ?string $businessContactName,
        public ?int $subjectiveRelevance,
        public string $status,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'company' => $this->company,
            'title' => $this->title,
            'contractType' => $this->contractType,
            'location' => $this->location,
            'remoteMode' => $this->remoteMode,
            'remuneration' => $this->remuneration,
            'offerUrl' => $this->offerUrl,
            'notes' => $this->notes,
            'applicationDate' => $this->applicationDate,
            'plannedFollowUpDate' => $this->plannedFollowUpDate,
            'effectiveFollowUpDate' => $this->effectiveFollowUpDate,
            'firstContactDate' => $this->firstContactDate,
            'preliminaryInterviewDate' => $this->preliminaryInterviewDate,
            'secondInterviewDate' => $this->secondInterviewDate,
            'hrContactName' => $this->hrContactName,
            'businessContactName' => $this->businessContactName,
            'subjectiveRelevance' => $this->subjectiveRelevance,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
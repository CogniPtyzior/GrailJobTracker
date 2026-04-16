export type ContractType = 'CDI' | 'CDD' | 'FREELANCE' | 'INTERNSHIP' | 'APPRENTICESHIP' | 'OTHER';
export type RemoteMode = 'NON' | 'HYBRID' | 'FLEXIBLE_HYBRID' | 'FULL';
export type TrackedJobStatus =
  | 'DRAFT'
  | 'APPLIED'
  | 'FOLLOW_UP_PENDING'
  | 'FOLLOW_UP_DONE'
  | 'FIRST_CONTACT'
  | 'PRELIMINARY_INTERVIEW'
  | 'SECOND_INTERVIEW'
  | 'OFFER_RECEIVED'
  | 'HIRED'
  | 'REJECTED'
  | 'WITHDRAWN';

export interface TrackedJob {
  id: string;
  company: string | null;
  title: string | null;
  contractType: ContractType | null;
  location: string | null;
  remoteMode: RemoteMode | null;
  remuneration: string | null;
  offerUrl: string | null;
  notes: string | null;
  applicationDate: string | null;
  plannedFollowUpDate: string | null;
  effectiveFollowUpDate: string | null;
  firstContactDate: string | null;
  preliminaryInterviewDate: string | null;
  secondInterviewDate: string | null;
  hrContactName: string | null;
  businessContactName: string | null;
  subjectiveRelevance: number | null;
  status: TrackedJobStatus;
  createdAt: string;
  updatedAt: string;
}

export interface TrackedJobFilters {
  search: string;
  company: string;
  status: TrackedJobStatus | '';
  contractType: ContractType | '';
  remoteMode: RemoteMode | '';
}

export interface PaginatedResponse<T> {
  items: T[];
  page: number;
  pageSize: number;
  hasMore: boolean;
}

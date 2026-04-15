import { ContractType, RemoteMode, TrackedJobStatus } from '../models/tracked-job.models';

export const CONTRACT_TYPE_LABELS: Record<ContractType, string> = {
  CDI: 'CDI',
  CDD: 'CDD',
  FREELANCE: 'Freelance',
  INTERNSHIP: 'Stage',
  APPRENTICESHIP: 'Alternance',
  OTHER: 'Autre'
};

export const REMOTE_MODE_LABELS: Record<RemoteMode, string> = {
  NON: 'Non',
  HYBRID: 'Hybride',
  FLEXIBLE_HYBRID: 'Hybride flexible',
  FULL: 'Full remote'
};

export const TRACKED_JOB_STATUS_LABELS: Record<TrackedJobStatus, string> = {
  DRAFT: 'Brouillon',
  APPLIED: 'Candidature envoyée',
  FOLLOW_UP_PENDING: 'Relance à faire',
  FOLLOW_UP_DONE: 'Relance effectuée',
  FIRST_CONTACT: 'Premier contact',
  PRELIMINARY_INTERVIEW: 'Entretien préliminaire',
  SECOND_INTERVIEW: 'Deuxième entretien',
  OFFER_RECEIVED: 'Offre reçue',
  HIRED: 'Recruté',
  REJECTED: 'Refusé',
  WITHDRAWN: 'Retiré'
};

export const FINAL_STATUSES: TrackedJobStatus[] = ['OFFER_RECEIVED', 'HIRED', 'REJECTED', 'WITHDRAWN'];

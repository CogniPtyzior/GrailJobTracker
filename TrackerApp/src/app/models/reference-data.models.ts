import { ContractType, RemoteMode, TrackedJobStatus } from './tracked-job.models';

export interface ReferenceData {
  contractTypes: ContractType[];
  remoteModes: RemoteMode[];
  trackedJobStatuses: TrackedJobStatus[];
  defaultContractType: ContractType;
}

export interface AccessRequestPayload {
  email: string;
  companyName: string;
  reason: string;
  firstName?: string | null;
  lastName?: string | null;
}

export interface AccessRequestItem {
  id: string;
  email: string;
  companyName: string;
  reason: string;
  firstName: string | null;
  lastName: string | null;
  createdAt: string;
}

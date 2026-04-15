import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE_URL } from './api.config';
import { AccessRequestItem, AccessRequestPayload } from '../models/access-request.models';
import { PaginatedResponse } from '../models/tracked-job.models';

@Injectable({ providedIn: 'root' })
export class AccessRequestService {
  private readonly http = inject(HttpClient);

  submit(payload: AccessRequestPayload): Observable<void> {
    return this.http.post<void>(`${API_BASE_URL}/access-requests`, payload);
  }

  list(query: string, page: number, pageSize: number): Observable<PaginatedResponse<AccessRequestItem>> {
    const params = new HttpParams()
      .set('query', query)
      .set('page', page)
      .set('pageSize', pageSize);

    return this.http.get<PaginatedResponse<AccessRequestItem>>(`${API_BASE_URL}/admin/access-requests`, { params });
  }

  approve(id: string, payload: { password: string; firstName?: string; lastName?: string }): Observable<{ item: { id: string; email: string } }> {
    return this.http.post<{ item: { id: string; email: string } }>(`${API_BASE_URL}/admin/access-requests/${id}/approve`, payload);
  }

  remove(id: string): Observable<void> {
    return this.http.delete<void>(`${API_BASE_URL}/admin/access-requests/${id}`);
  }
}

import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE_URL } from './api.config';
import { PaginatedResponse } from '../models/tracked-job.models';
import { User } from '../models/auth.models';

export interface AdminUserPayload {
  email?: string;
  password?: string;
  firstName?: string | null;
  lastName?: string | null;
  isActive?: boolean;
  isAdmin?: boolean;
}

@Injectable({ providedIn: 'root' })
export class AdminUserService {
  private readonly http = inject(HttpClient);

  list(query: string, isActive: '' | 'true' | 'false', page: number, pageSize: number): Observable<PaginatedResponse<User>> {
    let params = new HttpParams()
      .set('query', query)
      .set('page', page)
      .set('pageSize', pageSize);

    if (isActive) {
      params = params.set('isActive', isActive);
    }

    return this.http.get<PaginatedResponse<User>>(`${API_BASE_URL}/admin/users`, { params });
  }

  create(payload: Required<Pick<AdminUserPayload, 'email' | 'password'>> & AdminUserPayload): Observable<{ item: User }> {
    return this.http.post<{ item: User }>(`${API_BASE_URL}/admin/users`, payload);
  }

  update(id: string, payload: AdminUserPayload): Observable<{ item: User }> {
    return this.http.put<{ item: User }>(`${API_BASE_URL}/admin/users/${id}`, payload);
  }

  remove(id: string): Observable<void> {
    return this.http.delete<void>(`${API_BASE_URL}/admin/users/${id}`);
  }
}

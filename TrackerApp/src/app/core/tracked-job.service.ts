import { HttpClient, HttpParams } from '@angular/common/http';
import { inject, Injectable } from '@angular/core';
import { Observable } from 'rxjs';
import { API_BASE_URL } from './api.config';
import { PaginatedResponse, TrackedJob, TrackedJobFilters } from '../models/tracked-job.models';

@Injectable({ providedIn: 'root' })
export class TrackedJobService {
  private readonly http = inject(HttpClient);

  list(filters: TrackedJobFilters, page: number, pageSize: number): Observable<PaginatedResponse<TrackedJob>> {
    let params = new HttpParams()
      .set('page', page)
      .set('pageSize', pageSize);

    for (const [key, value] of Object.entries(filters)) {
      if (value) {
        params = params.set(key, value);
      }
    }

    return this.http.get<PaginatedResponse<TrackedJob>>(`${API_BASE_URL}/tracked-jobs`, { params });
  }

  get(id: string): Observable<{ item: TrackedJob }> {
    return this.http.get<{ item: TrackedJob }>(`${API_BASE_URL}/tracked-jobs/${id}`);
  }

  create(payload: Partial<TrackedJob>): Observable<{ item: TrackedJob }> {
    return this.http.post<{ item: TrackedJob }>(`${API_BASE_URL}/tracked-jobs`, payload);
  }

  update(id: string, payload: Partial<TrackedJob>): Observable<{ item: TrackedJob }> {
    return this.http.put<{ item: TrackedJob }>(`${API_BASE_URL}/tracked-jobs/${id}`, payload);
  }

  remove(id: string): Observable<void> {
    return this.http.delete<void>(`${API_BASE_URL}/tracked-jobs/${id}`);
  }

  searchCompanies(query: string): Observable<{ items: string[] }> {
    return this.http.get<{ items: string[] }>(`${API_BASE_URL}/tracked-jobs/company-suggestions`, {
      params: new HttpParams().set('q', query)
    });
  }

  exportCsv(filters: TrackedJobFilters): Observable<Blob> {
    return this.http.post(`${API_BASE_URL}/tracked-jobs/export-csv`, this.toFilterPayload(filters), {
      responseType: 'blob'
    });
  }

  private toFilterPayload(filters: TrackedJobFilters): Partial<TrackedJobFilters> {
    return Object.fromEntries(
      Object.entries(filters).filter(([, value]) => value !== '')
    ) as Partial<TrackedJobFilters>;
  }
}

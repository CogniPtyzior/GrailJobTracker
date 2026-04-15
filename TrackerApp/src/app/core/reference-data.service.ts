import { inject, Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, shareReplay } from 'rxjs';
import { ReferenceData } from '../models/reference-data.models';
import { API_BASE_URL } from './api.config';

@Injectable({ providedIn: 'root' })
export class ReferenceDataService {
  private readonly http = inject(HttpClient);

  private readonly referenceData$ = this.http.get<ReferenceData>(`${API_BASE_URL}/reference-data`).pipe(
    shareReplay(1)
  );

  getReferenceData(): Observable<ReferenceData> {
    return this.referenceData$;
  }
}

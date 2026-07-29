import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { afterEach, describe, it } from 'vitest';

import { TrackedJobService } from './tracked-job.service';

describe('TrackedJobService', () => {
  afterEach(() => {
    TestBed.inject(HttpTestingController).verify();
    TestBed.resetTestingModule();
  });

  it('builds list query params and skips empty filters', () => {
    TestBed.configureTestingModule({
      providers: [
        TrackedJobService,
        provideHttpClient(),
        provideHttpClientTesting()
      ]
    });

    TestBed.inject(TrackedJobService).list({
      search: '',
      company: 'Acme',
      status: 'APPLIED',
      contractType: '',
      remoteMode: ''
    }, 2, 25).subscribe();

    const request = TestBed.inject(HttpTestingController).expectOne((httpRequest) => {
      return httpRequest.url === '/api/tracked-jobs'
        && httpRequest.params.get('page') === '2'
        && httpRequest.params.get('pageSize') === '25'
        && httpRequest.params.get('company') === 'Acme'
        && httpRequest.params.get('status') === 'APPLIED'
        && !httpRequest.params.has('search')
        && !httpRequest.params.has('contractType')
        && !httpRequest.params.has('remoteMode');
    });

    request.flush({ items: [], page: 2, pageSize: 25, hasMore: false });
  });
});

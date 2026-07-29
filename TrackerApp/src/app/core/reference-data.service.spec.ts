import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { afterEach, describe, expect, it } from 'vitest';

import { ReferenceDataService } from './reference-data.service';
import { ReferenceData } from '../models/reference-data.models';

describe('ReferenceDataService', () => {
  afterEach(() => {
    TestBed.inject(HttpTestingController).verify();
    TestBed.resetTestingModule();
  });

  it('caches reference data across subscribers', () => {
    const referenceData: ReferenceData = {
      contractTypes: ['CDI'],
      remoteModes: ['HYBRID'],
      trackedJobStatuses: ['DRAFT'],
      defaultContractType: 'CDI'
    };

    TestBed.configureTestingModule({
      providers: [
        ReferenceDataService,
        provideHttpClient(),
        provideHttpClientTesting()
      ]
    });

    const service = TestBed.inject(ReferenceDataService);

    service.getReferenceData().subscribe((result) => expect(result).toEqual(referenceData));
    service.getReferenceData().subscribe((result) => expect(result).toEqual(referenceData));

    TestBed.inject(HttpTestingController).expectOne('/api/reference-data').flush(referenceData);
  });
});

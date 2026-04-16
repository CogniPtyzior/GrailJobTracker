import { Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { debounceTime, distinctUntilChanged, take } from 'rxjs';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { ReferenceDataService } from '../core/reference-data.service';
import { TrackedJobService } from '../core/tracked-job.service';
import { CONTRACT_TYPE_LABELS, REMOTE_MODE_LABELS, TRACKED_JOB_STATUS_LABELS } from '../core/label-dictionary';
import { ReferenceData } from '../models/reference-data.models';
import { TrackedJob, TrackedJobFilters } from '../models/tracked-job.models';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule, MatSelectModule, MatProgressSpinnerModule],
  templateUrl: './tracked-jobs-page.component.html',
  styleUrls: ['./tracked-jobs-page.component.scss']
})
export class TrackedJobsPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrackedJobService);
  private readonly referenceDataService = inject(ReferenceDataService);
  private readonly destroyRef = inject(DestroyRef);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly items = signal<TrackedJob[]>([]);
  protected readonly hasMore = signal(false);
  protected readonly page = signal(1);
  protected readonly pageSize = 10;
  protected readonly expandedId = signal<string | null>(null);
  protected readonly referenceData = signal<ReferenceData | null>(null);
  protected readonly listLoading = signal(false);
  protected readonly referenceDataLoading = signal(false);
  protected readonly exportingCsv = signal(false);
  protected readonly filtersBusy = computed(() => this.listLoading() || this.referenceDataLoading());
  protected readonly resultsBusy = computed(() => this.listLoading());

  protected readonly filtersForm = this.fb.nonNullable.group({
    search: [''],
    company: [this.route.snapshot.queryParamMap.get('company') ?? ''],
    status: [''],
    contractType: [''],
    remoteMode: ['']
  });

  protected readonly filters = computed<TrackedJobFilters>(() => this.filtersForm.getRawValue() as TrackedJobFilters);

  public constructor() {
    this.referenceDataLoading.set(true);
    this.referenceDataService.getReferenceData().pipe(take(1)).subscribe({
      next: (data) => this.referenceData.set(data),
      complete: () => this.referenceDataLoading.set(false)
    });

    this.filtersForm.valueChanges.pipe(
      debounceTime(250),
      distinctUntilChanged((previous, current) => JSON.stringify(previous) === JSON.stringify(current)),
      takeUntilDestroyed(this.destroyRef)
    ).subscribe(() => {
      this.page.set(1);
      this.load();
    });

    this.load();
  }

  protected load(): void {
    const filters = this.filters();
    this.listLoading.set(true);

    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { company: filters.company || null },
      queryParamsHandling: 'merge',
      replaceUrl: true
    });

    this.service.list(filters, this.page(), this.pageSize).pipe(take(1)).subscribe({
      next: (response) => {
        this.items.set(response.items);
        this.hasMore.set(response.hasMore);
      },
      complete: () => this.listLoading.set(false)
    });
  }

  protected previousPage(): void {
    if (this.page() > 1 && !this.resultsBusy()) {
      this.page.update((value) => value - 1);
      this.load();
    }
  }

  protected nextPage(): void {
    if (this.hasMore() && !this.resultsBusy()) {
      this.page.update((value) => value + 1);
      this.load();
    }
  }

  protected toggleExpanded(id: string): void {
    if (this.resultsBusy()) {
      return;
    }

    this.expandedId.set(this.expandedId() === id ? null : id);
  }

  protected exportCsv(): void {
    if (this.exportingCsv()) {
      return;
    }

    this.exportingCsv.set(true);
    this.service.exportCsv(this.filters()).pipe(take(1)).subscribe({
      next: (blob) => {
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = 'tracked-jobs.csv';
        anchor.click();
        URL.revokeObjectURL(url);
      },
      complete: () => this.exportingCsv.set(false)
    });
  }

  protected statusLabel(status: string): string {
    return TRACKED_JOB_STATUS_LABELS[status as keyof typeof TRACKED_JOB_STATUS_LABELS] ?? status;
  }

  protected contractTypeLabel(contractType: string): string {
    return CONTRACT_TYPE_LABELS[contractType as keyof typeof CONTRACT_TYPE_LABELS] ?? contractType;
  }

  protected remoteModeLabel(remoteMode: string): string {
    return REMOTE_MODE_LABELS[remoteMode as keyof typeof REMOTE_MODE_LABELS] ?? remoteMode;
  }

  protected formatDate(value: string | null): string {
    if (!value) {
      return '—';
    }

    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium' }).format(new Date(value));
  }
}

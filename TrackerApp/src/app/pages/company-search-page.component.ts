import { Component, DestroyRef, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { catchError, debounceTime, distinctUntilChanged, filter, finalize, of, switchMap, tap } from 'rxjs';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { TrackedJobService } from '../core/tracked-job.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatFormFieldModule, MatInputModule, MatProgressSpinnerModule],
  templateUrl: './company-search-page.component.html',
  styleUrls: ['./company-search-page.component.scss']
})
export class CompanySearchPageComponent {
  private readonly trackedJobService = inject(TrackedJobService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  private readonly activeSearches = signal(0);
  protected readonly searchControl = new FormControl<string>('', { nonNullable: true });
  protected readonly results = signal<string[]>([]);
  protected readonly searching = computed(() => this.activeSearches() > 0);
  protected readonly showResultsPanel = computed(() => this.searchControl.value.length >= 3 || this.searching());

  public constructor() {
    this.searchControl.valueChanges.pipe(
      debounceTime(250),
      distinctUntilChanged(),
      tap((value) => {
        if (value.length < 3) {
          this.results.set([]);
        }
      }),
      filter((value) => value.length >= 3),
      tap(() => this.activeSearches.update((value) => value + 1)),
      switchMap((query) => this.trackedJobService.searchCompanies(query).pipe(
        catchError(() => of({ items: [] })),
        finalize(() => this.activeSearches.update((value) => Math.max(0, value - 1)))
      )),
      takeUntilDestroyed(this.destroyRef)
    ).subscribe((response) => this.results.set(response.items));
  }

  protected openCompany(company: string): void {
    void this.router.navigate(['/tracked-jobs'], { queryParams: { company } });
  }

  protected createNew(): void {
    const company = this.searchControl.value.trim();

    void this.router.navigate(['/tracked-jobs/new'], {
      queryParams: company ? { company } : undefined
    });
  }

  protected goToList(): void {
    void this.router.navigate(['/tracked-jobs']);
  }
}

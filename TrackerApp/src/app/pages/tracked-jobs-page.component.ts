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
import { ReferenceDataService } from '../core/reference-data.service';
import { TrackedJobService } from '../core/tracked-job.service';
import { CONTRACT_TYPE_LABELS, REMOTE_MODE_LABELS, TRACKED_JOB_STATUS_LABELS } from '../core/label-dictionary';
import { ReferenceData } from '../models/reference-data.models';
import { TrackedJob, TrackedJobFilters } from '../models/tracked-job.models';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <section class="surface-card">
      <div class="section-header">
        <div>
          <h1 class="section-title">Mes candidatures</h1>
          <p class="section-subtitle">Vue priorisée par relances et avancée du pipeline.</p>
        </div>
        <div class="spacer"></div>
        <button mat-flat-button color="primary" routerLink="/tracked-jobs/new">Nouvelle fiche</button>
        <button mat-button (click)="exportCsv()">Exporter CSV</button>
      </div>

      <form [formGroup]="filtersForm" class="grid-3 form-section">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Recherche</mat-label>
          <input matInput formControlName="search">
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Entreprise</mat-label>
          <input matInput formControlName="company">
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Statut</mat-label>
          <mat-select formControlName="status">
            <mat-option value="">Tous</mat-option>
            @for (status of referenceData()?.trackedJobStatuses ?? []; track status) {
              <mat-option [value]="status">{{ statusLabel(status) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Type de contrat</mat-label>
          <mat-select formControlName="contractType">
            <mat-option value="">Tous</mat-option>
            @for (type of referenceData()?.contractTypes ?? []; track type) {
              <mat-option [value]="type">{{ contractTypeLabel(type) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Télétravail</mat-label>
          <mat-select formControlName="remoteMode">
            <mat-option value="">Tous</mat-option>
            @for (mode of referenceData()?.remoteModes ?? []; track mode) {
              <mat-option [value]="mode">{{ remoteModeLabel(mode) }}</mat-option>
            }
          </mat-select>
        </mat-form-field>
      </form>
    </section>

    <section class="tracked-jobs-section">
      <div class="inline-actions tracked-jobs-toolbar">
        <div class="status-chip">{{ total() }} fiche(s)</div>
        <div class="status-chip">Page {{ page() }}</div>
      </div>

      @for (job of items(); track job.id) {
        <article class="table-row-card tracked-job-card">
          <div class="row-summary">
            <div>
              <div class="summary-title">{{ job.company || 'Entreprise à compléter' }}</div>
              <div class="summary-muted">{{ job.title || 'Poste à compléter' }}</div>
            </div>

            <div>
              <div class="status-chip">{{ statusLabel(job.status) }}</div>
            </div>

            <div>
              <div class="summary-value">Relance prévue</div>
              <div class="summary-muted">{{ formatDate(job.plannedFollowUpDate) }}</div>
            </div>

            <div class="inline-actions row-actions">
              <button mat-stroked-button (click)="toggleExpanded(job.id)">
                {{ expandedId() === job.id ? 'Masquer' : 'Voir' }}
              </button>
              <button mat-flat-button color="primary" [routerLink]="['/tracked-jobs', job.id]">Éditer</button>
            </div>
          </div>

          @if (expandedId() === job.id) {
            <div class="row-details">
              <div class="metric-grid">
                <div class="metric-card">
                  <div class="metric-label">Contrat</div>
                  <div>{{ job.contractType ? contractTypeLabel(job.contractType) : '—' }}</div>
                </div>
                <div class="metric-card">
                  <div class="metric-label">Télétravail</div>
                  <div>{{ job.remoteMode ? remoteModeLabel(job.remoteMode) : '—' }}</div>
                </div>
                <div class="metric-card">
                  <div class="metric-label">Pertinence</div>
                  <div>{{ job.subjectiveRelevance ?? '—' }}/10</div>
                </div>
              </div>

              <div class="grid-3 tracked-job-details-grid">
                <div><strong>Candidature</strong><br>{{ formatDate(job.applicationDate) }}</div>
                <div><strong>Relance faite</strong><br>{{ formatDate(job.effectiveFollowUpDate) }}</div>
                <div><strong>Premier contact</strong><br>{{ formatDate(job.firstContactDate) }}</div>
                <div><strong>Entretien préliminaire</strong><br>{{ formatDate(job.preliminaryInterviewDate) }}</div>
                <div><strong>Deuxième entretien</strong><br>{{ formatDate(job.secondInterviewDate) }}</div>
                <div><strong>Lieu</strong><br>{{ job.location || '—' }}</div>
              </div>

              <div class="tracked-job-notes">
                <strong>Notes</strong>
                <p class="notes-content">{{ job.notes || 'Aucune note' }}</p>
              </div>
            </div>
          }
        </article>
      } @empty {
        <div class="surface-card">
          <h2 class="section-title">Aucune candidature</h2>
          <p class="section-subtitle">Créez votre première fiche pour commencer.</p>
        </div>
      }

      <div class="inline-actions tracked-jobs-pagination">
        <button mat-stroked-button (click)="previousPage()" [disabled]="page() === 1">Précédent</button>
        <button mat-flat-button color="primary" (click)="nextPage()" [disabled]="page() * pageSize >= total()">Suivant</button>
      </div>
    </section>
  `,
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
  protected readonly total = signal(0);
  protected readonly page = signal(1);
  protected readonly pageSize = 10;
  protected readonly expandedId = signal<string | null>(null);
  protected readonly referenceData = signal<ReferenceData | null>(null);

  protected readonly filtersForm = this.fb.nonNullable.group({
    search: [''],
    company: [this.route.snapshot.queryParamMap.get('company') ?? ''],
    status: [''],
    contractType: [''],
    remoteMode: ['']
  });

  protected readonly filters = computed<TrackedJobFilters>(() => this.filtersForm.getRawValue() as TrackedJobFilters);

  public constructor() {
    this.referenceDataService.getReferenceData().pipe(take(1)).subscribe((data) => this.referenceData.set(data));

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
    void this.router.navigate([], {
      relativeTo: this.route,
      queryParams: { company: filters.company || null },
      queryParamsHandling: 'merge',
      replaceUrl: true
    });

    this.service.list(filters, this.page(), this.pageSize).pipe(take(1)).subscribe((response) => {
      this.items.set(response.items);
      this.total.set(response.total);
    });
  }

  protected previousPage(): void {
    if (this.page() > 1) {
      this.page.update((value) => value - 1);
      this.load();
    }
  }

  protected nextPage(): void {
    if (this.page() * this.pageSize < this.total()) {
      this.page.update((value) => value + 1);
      this.load();
    }
  }

  protected toggleExpanded(id: string): void {
    this.expandedId.set(this.expandedId() === id ? null : id);
  }

  protected exportCsv(): void {
    this.service.exportCsv(this.filters()).pipe(take(1)).subscribe((blob) => {
      const url = URL.createObjectURL(blob);
      const anchor = document.createElement('a');
      anchor.href = url;
      anchor.download = 'tracked-jobs.csv';
      anchor.click();
      URL.revokeObjectURL(url);
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

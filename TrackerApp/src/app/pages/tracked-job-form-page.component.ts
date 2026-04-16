import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { take } from 'rxjs';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MAT_DATE_LOCALE, MatNativeDateModule } from '@angular/material/core';
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatDialog } from '@angular/material/dialog';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatIconModule } from '@angular/material/icon';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { FINAL_STATUSES, CONTRACT_TYPE_LABELS, REMOTE_MODE_LABELS, TRACKED_JOB_STATUS_LABELS } from '../core/label-dictionary';
import { ReferenceDataService } from '../core/reference-data.service';
import { TrackedJobService } from '../core/tracked-job.service';
import { ReferenceData } from '../models/reference-data.models';
import { TrackedJob } from '../models/tracked-job.models';
import { ConfirmDialogComponent } from '../shared/confirm-dialog.component';

@Component({
  standalone: true,
  providers: [{ provide: MAT_DATE_LOCALE, useValue: 'fr-FR' }],
  imports: [
    ReactiveFormsModule,
    RouterLink,
    MatAutocompleteModule,
    MatButtonModule,
    MatCardModule,
    MatDatepickerModule,
    MatNativeDateModule,
    MatFormFieldModule,
    MatIconModule,
    MatInputModule,
    MatSelectModule,
    MatProgressSpinnerModule
  ],
  templateUrl: './tracked-job-form-page.component.html',
  styleUrls: ['./tracked-job-form-page.component.scss']
})
export class TrackedJobFormPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrackedJobService);
  private readonly referenceDataService = inject(ReferenceDataService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly dialog = inject(MatDialog);
  private readonly pendingInitialLoads = signal(0);

  protected readonly saving = signal(false);
  protected readonly deleting = signal(false);
  protected readonly errorMessage = signal('');
  protected readonly referenceData = signal<ReferenceData | null>(null);
  protected readonly companySuggestions = signal<string[]>([]);
  protected readonly isEditMode = signal(false);
  protected readonly currentId = signal<string | null>(null);
  protected readonly finalStatuses = FINAL_STATUSES;
  protected readonly pageLoading = computed(() => this.pendingInitialLoads() > 0);
  protected readonly loading = computed(() => this.pageLoading() || this.saving() || this.deleting());
  protected readonly loadingMessage = computed(() => {
    if (this.deleting()) {
      return 'Suppression de la candidature…';
    }

    if (this.saving()) {
      return 'Enregistrement de la candidature…';
    }

    return 'Chargement de la candidature…';
  });

  protected readonly form = this.fb.group({
    company: [this.route.snapshot.queryParamMap.get('company') ?? '', [Validators.required]],
    title: ['', [Validators.required]],
    contractType: ['CDI'],
    location: [''],
    remoteMode: [''],
    remuneration: [''],
    offerUrl: [''],
    notes: [''],
    applicationDate: [null as Date | string | null],
    effectiveFollowUpDate: [null as Date | string | null],
    firstContactDate: [null as Date | string | null],
    preliminaryInterviewDate: [null as Date | string | null],
    secondInterviewDate: [null as Date | string | null],
    hrContactName: [''],
    businessContactName: [''],
    subjectiveRelevance: [''],
    status: ['']
  });

  public constructor() {
    this.loadReferenceData();

    const id = this.route.snapshot.paramMap.get('id');
    const draftTemplate = history.state?.draftTemplate as Partial<TrackedJob> | undefined;

    if (id) {
      this.isEditMode.set(true);
      this.currentId.set(id);
      this.loadTrackedJob(id);
    } else if (draftTemplate) {
      this.patchDraftTemplate(draftTemplate);
    }

    this.form.controls.company.valueChanges.subscribe((value) => {
      if ((value?.length ?? 0) >= 3) {
        this.service.searchCompanies(value ?? '').pipe(take(1)).subscribe((response) => this.companySuggestions.set(response.items));
      } else {
        this.companySuggestions.set([]);
      }
    });
  }

  protected createAnotherApplication(): void {
    const raw = this.form.getRawValue();

    void this.router.navigate(['/tracked-jobs/new'], {
      state: {
        draftTemplate: {
          company: raw.company,
          title: raw.title,
          contractType: raw.contractType,
          location: raw.location,
          remoteMode: raw.remoteMode,
          remuneration: raw.remuneration,
          offerUrl: raw.offerUrl,
          notes: raw.notes,
          hrContactName: raw.hrContactName,
          businessContactName: raw.businessContactName,
          subjectiveRelevance: raw.subjectiveRelevance
        }
      }
    });
  }

  private patchDraftTemplate(template: Partial<TrackedJob>): void {
    this.form.patchValue({
      company: template.company ?? '',
      title: template.title ?? '',
      contractType: template.contractType ?? 'CDI',
      location: template.location ?? '',
      remoteMode: template.remoteMode ?? '',
      remuneration: template.remuneration ?? '',
      offerUrl: template.offerUrl ?? '',
      notes: template.notes ?? '',
      hrContactName: template.hrContactName ?? '',
      businessContactName: template.businessContactName ?? '',
      subjectiveRelevance: template.subjectiveRelevance?.toString() ?? '',

      applicationDate: null,
      effectiveFollowUpDate: null,
      firstContactDate: null,
      preliminaryInterviewDate: null,
      secondInterviewDate: null,
      status: ''
    });
  }

  protected plannedFollowUpDateDisplay(): string {
    const applicationDate = this.coerceDate(this.form.controls.applicationDate.value);

    if (!applicationDate) {
      return 'Calculée automatiquement après saisie';
    }

    const baseDate = new Date(applicationDate);
    baseDate.setDate(baseDate.getDate() + 15);

    return baseDate.toLocaleDateString('fr-FR');
  }

  protected save(): void {
    if (this.form.invalid || this.loading()) {
      return;
    }

    this.saving.set(true);
    this.errorMessage.set('');

    const payload = this.toPayload();

    const request$ = this.isEditMode() && this.currentId()
      ? this.service.update(this.currentId()!, payload)
      : this.service.create(payload);

    request$.pipe(take(1)).subscribe({
      next: (response) => {
        this.saving.set(false);
        void this.router.navigate(['/tracked-jobs', response.item.id]);
      },
      error: () => {
        this.saving.set(false);
        this.errorMessage.set('Enregistrement impossible.');
      }
    });
  }

  protected remove(): void {
    const id = this.currentId();

    if (!id || this.loading()) {
      return;
    }

    this.dialog.open(ConfirmDialogComponent, {
      width: '420px',
      data: {
        title: 'Supprimer la candidature ?',
        message: 'Cette action est définitive. La fiche sera supprimée de la liste et ne pourra pas être restaurée.',
        confirmLabel: 'Supprimer',
        cancelLabel: 'Annuler'
      }
    }).afterClosed().pipe(take(1)).subscribe((confirmed) => {
      if (!confirmed) {
        return;
      }

      this.deleting.set(true);
      this.service.remove(id).pipe(take(1)).subscribe({
        next: () => void this.router.navigate(['/tracked-jobs']),
        error: () => {
          this.deleting.set(false);
          this.errorMessage.set('Suppression impossible.');
        }
      });
    });
  }

  protected contractTypeLabel(contractType: string): string {
    return CONTRACT_TYPE_LABELS[contractType as keyof typeof CONTRACT_TYPE_LABELS] ?? contractType;
  }

  protected remoteModeLabel(remoteMode: string): string {
    return REMOTE_MODE_LABELS[remoteMode as keyof typeof REMOTE_MODE_LABELS] ?? remoteMode;
  }

  protected statusLabel(status: string): string {
    return TRACKED_JOB_STATUS_LABELS[status as keyof typeof TRACKED_JOB_STATUS_LABELS] ?? status;
  }

  private loadReferenceData(): void {
    this.beginInitialLoad();
    this.referenceDataService.getReferenceData().pipe(take(1)).subscribe({
      next: (data) => this.referenceData.set(data),
      error: () => this.errorMessage.set('Impossible de charger les données de référence.'),
      complete: () => this.endInitialLoad()
    });
  }

  private loadTrackedJob(id: string): void {
    this.beginInitialLoad();
    this.service.get(id).pipe(take(1)).subscribe({
      next: (response) => this.patchForm(response.item),
      error: () => this.errorMessage.set('Impossible de charger la fiche.'),
      complete: () => this.endInitialLoad()
    });
  }

  private beginInitialLoad(): void {
    this.pendingInitialLoads.update((value) => value + 1);
  }

  private endInitialLoad(): void {
    this.pendingInitialLoads.update((value) => Math.max(0, value - 1));
  }

  private patchForm(item: TrackedJob): void {
    this.form.patchValue({
      company: item.company ?? '',
      title: item.title ?? '',
      contractType: item.contractType ?? 'CDI',
      location: item.location ?? '',
      remoteMode: item.remoteMode ?? '',
      remuneration: item.remuneration ?? '',
      offerUrl: item.offerUrl ?? '',
      notes: item.notes ?? '',
      applicationDate: this.toDateValue(item.applicationDate),
      effectiveFollowUpDate: this.toDateValue(item.effectiveFollowUpDate),
      firstContactDate: this.toDateValue(item.firstContactDate),
      preliminaryInterviewDate: this.toDateValue(item.preliminaryInterviewDate),
      secondInterviewDate: this.toDateValue(item.secondInterviewDate),
      hrContactName: item.hrContactName ?? '',
      businessContactName: item.businessContactName ?? '',
      subjectiveRelevance: item.subjectiveRelevance?.toString() ?? '',
      status: this.finalStatuses.includes(item.status) ? item.status : ''
    });
  }

  private toPayload(): Record<string, unknown> {
    const raw = this.form.getRawValue();

    return {
      company: raw.company,
      title: raw.title,
      contractType: raw.contractType || null,
      location: raw.location || null,
      remoteMode: raw.remoteMode || null,
      remuneration: raw.remuneration || null,
      offerUrl: raw.offerUrl || null,
      notes: raw.notes || null,
      applicationDate: this.toIsoDate(raw.applicationDate),
      effectiveFollowUpDate: this.toIsoDate(raw.effectiveFollowUpDate),
      firstContactDate: this.toIsoDate(raw.firstContactDate),
      preliminaryInterviewDate: this.toIsoDate(raw.preliminaryInterviewDate),
      secondInterviewDate: this.toIsoDate(raw.secondInterviewDate),
      hrContactName: raw.hrContactName || null,
      businessContactName: raw.businessContactName || null,
      subjectiveRelevance: raw.subjectiveRelevance ? Number(raw.subjectiveRelevance) : null,
      status: raw.status || null
    };
  }

  private toIsoDate(value: Date | string | null | undefined): string | null {
    const date = this.coerceDate(value);
    return date ? new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate())).toISOString() : null;
  }

  private toDateValue(value: string | null): Date | null {
    if (!value) {
      return null;
    }

    const [year, month, day] = value.substring(0, 10).split('-').map(Number);
    return new Date(year, month - 1, day);
  }

  private coerceDate(value: Date | string | null | undefined): Date | null {
    if (!value) {
      return null;
    }

    if (value instanceof Date) {
      return Number.isNaN(value.getTime()) ? null : value;
    }

    const normalized = value.length <= 10 ? `${value}T00:00:00` : value;
    const parsed = new Date(normalized);
    return Number.isNaN(parsed.getTime()) ? null : parsed;
  }
}

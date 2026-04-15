import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { take } from 'rxjs';
import { MatAutocompleteModule } from '@angular/material/autocomplete';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatSelectModule } from '@angular/material/select';
import { FINAL_STATUSES, CONTRACT_TYPE_LABELS, REMOTE_MODE_LABELS, TRACKED_JOB_STATUS_LABELS } from '../core/label-dictionary';
import { ReferenceDataService } from '../core/reference-data.service';
import { TrackedJobService } from '../core/tracked-job.service';
import { ReferenceData } from '../models/reference-data.models';
import { TrackedJob } from '../models/tracked-job.models';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatAutocompleteModule, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule, MatSelectModule],
  template: `
    <section class="surface-card">
      <div class="section-header">
        <div>
          <h1 class="section-title">{{ isEditMode() ? 'Éditer une candidature' : 'Nouvelle candidature' }}</h1>
          <p class="section-subtitle">Les statuts intermédiaires sont recalculés automatiquement selon les dates.</p>
        </div>
        <div class="spacer"></div>
        <a mat-button routerLink="/tracked-jobs">Retour à la liste</a>
      </div>

      <form [formGroup]="form" (ngSubmit)="save()" class="form-section">
        <div class="grid-2">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Entreprise</mat-label>
            <input matInput formControlName="company" [matAutocomplete]="companyAuto">
            <mat-autocomplete #companyAuto="matAutocomplete">
              @for (company of companySuggestions(); track company) {
                <mat-option [value]="company">{{ company }}</mat-option>
              }
            </mat-autocomplete>
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Poste</mat-label>
            <input matInput formControlName="title">
          </mat-form-field>
        </div>

        <div class="grid-3">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Type de contrat</mat-label>
            <mat-select formControlName="contractType">
              @for (type of referenceData()?.contractTypes ?? []; track type) {
                <mat-option [value]="type">{{ contractTypeLabel(type) }}</mat-option>
              }
            </mat-select>
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Télétravail</mat-label>
            <mat-select formControlName="remoteMode">
              <mat-option [value]="''">Non renseigné</mat-option>
              @for (mode of referenceData()?.remoteModes ?? []; track mode) {
                <mat-option [value]="mode">{{ remoteModeLabel(mode) }}</mat-option>
              }
            </mat-select>
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Pertinence</mat-label>
            <input matInput type="number" min="1" max="10" formControlName="subjectiveRelevance">
          </mat-form-field>
        </div>

        <div class="grid-3">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Lieu</mat-label>
            <input matInput formControlName="location">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Rémunération</mat-label>
            <input matInput formControlName="remuneration">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>URL de l'offre</mat-label>
            <input matInput formControlName="offerUrl">
          </mat-form-field>
        </div>

        <div class="grid-3">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Date de candidature</mat-label>
            <input matInput type="date" formControlName="applicationDate">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Relance planifiée</mat-label>
            <input matInput [value]="plannedFollowUpDateDisplay()" readonly>
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Relance effectuée</mat-label>
            <input matInput type="date" formControlName="effectiveFollowUpDate">
          </mat-form-field>
        </div>

        <div class="grid-3">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Premier contact</mat-label>
            <input matInput type="date" formControlName="firstContactDate">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Entretien préliminaire</mat-label>
            <input matInput type="date" formControlName="preliminaryInterviewDate">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Deuxième entretien</mat-label>
            <input matInput type="date" formControlName="secondInterviewDate">
          </mat-form-field>
        </div>

        <div class="grid-3">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Contact RH</mat-label>
            <input matInput formControlName="hrContactName">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Contact métier</mat-label>
            <input matInput formControlName="businessContactName">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Statut final manuel</mat-label>
            <mat-select formControlName="status">
              <mat-option [value]="''">Aucun</mat-option>
              @for (status of finalStatuses; track status) {
                <mat-option [value]="status">{{ statusLabel(status) }}</mat-option>
              }
            </mat-select>
          </mat-form-field>
        </div>

        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Notes</mat-label>
          <textarea matInput rows="7" formControlName="notes"></textarea>
        </mat-form-field>

        @if (errorMessage()) {
          <p class="feedback-message feedback-message--error">{{ errorMessage() }}</p>
        }

        <div class="inline-actions">
          <button mat-flat-button color="primary" type="submit" [disabled]="form.invalid || loading()">
            Enregistrer
          </button>
          @if (isEditMode()) {
            <button mat-stroked-button type="button" (click)="remove()">Supprimer</button>
          }
        </div>
      </form>
    </section>
  `,
  styleUrls: ['./tracked-job-form-page.component.scss']
})
export class TrackedJobFormPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrackedJobService);
  private readonly referenceDataService = inject(ReferenceDataService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly loading = signal(false);
  protected readonly errorMessage = signal('');
  protected readonly referenceData = signal<ReferenceData | null>(null);
  protected readonly companySuggestions = signal<string[]>([]);
  protected readonly isEditMode = signal(false);
  protected readonly currentId = signal<string | null>(null);
  protected readonly finalStatuses = FINAL_STATUSES;

  protected readonly form = this.fb.nonNullable.group({
    company: [this.route.snapshot.queryParamMap.get('company') ?? '', [Validators.required]],
    title: ['', [Validators.required]],
    contractType: ['CDI'],
    location: [''],
    remoteMode: [''],
    remuneration: [''],
    offerUrl: [''],
    notes: [''],
    applicationDate: [''],
    effectiveFollowUpDate: [''],
    firstContactDate: [''],
    preliminaryInterviewDate: [''],
    secondInterviewDate: [''],
    hrContactName: [''],
    businessContactName: [''],
    subjectiveRelevance: [''],
    status: ['']
  });

  public constructor() {
    this.referenceDataService.getReferenceData().pipe(take(1)).subscribe((data) => this.referenceData.set(data));

    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.isEditMode.set(true);
      this.currentId.set(id);
      this.service.get(id).pipe(take(1)).subscribe({
        next: (response) => this.patchForm(response.item),
        error: () => this.errorMessage.set('Impossible de charger la fiche.')
      });
    }

    this.form.controls.company.valueChanges.subscribe((value) => {
      if ((value?.length ?? 0) >= 3) {
        this.service.searchCompanies(value).pipe(take(1)).subscribe((response) => this.companySuggestions.set(response.items));
      } else {
        this.companySuggestions.set([]);
      }
    });
  }

  protected plannedFollowUpDateDisplay(): string {
    const applicationDate = this.form.controls.applicationDate.value;

    if (!applicationDate) {
      return 'Calculée automatiquement après saisie';
    }

    const baseDate = new Date(`${applicationDate}T00:00:00`);
    baseDate.setDate(baseDate.getDate() + 15);

    return baseDate.toLocaleDateString('fr-FR');
  }

  protected save(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    const payload = this.toPayload();

    const request$ = this.isEditMode() && this.currentId()
      ? this.service.update(this.currentId()!, payload)
      : this.service.create(payload);

    request$.pipe(take(1)).subscribe({
      next: (response) => {
        this.loading.set(false);
        void this.router.navigate(['/tracked-jobs', response.item.id]);
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('Enregistrement impossible.');
      }
    });
  }

  protected remove(): void {
    const id = this.currentId();

    if (!id || !confirm('Supprimer cette candidature ?')) {
      return;
    }

    this.service.remove(id).pipe(take(1)).subscribe({
      next: () => void this.router.navigate(['/tracked-jobs']),
      error: () => this.errorMessage.set('Suppression impossible.')
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
      applicationDate: this.toDateInput(item.applicationDate),
      effectiveFollowUpDate: this.toDateInput(item.effectiveFollowUpDate),
      firstContactDate: this.toDateInput(item.firstContactDate),
      preliminaryInterviewDate: this.toDateInput(item.preliminaryInterviewDate),
      secondInterviewDate: this.toDateInput(item.secondInterviewDate),
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

  private toIsoDate(value: string): string | null {
    return value ? new Date(`${value}T00:00:00`).toISOString() : null;
  }

  private toDateInput(value: string | null): string {
    return value ? value.substring(0, 10) : '';
  }
}

import { Component, DestroyRef, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { debounceTime, distinctUntilChanged, filter, switchMap, tap } from 'rxjs/operators';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { MatButtonModule } from '@angular/material/button';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { TrackedJobService } from '../core/tracked-job.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatFormFieldModule, MatInputModule],
  template: `
    <section class="hero-card search-hero">
      <img src="assets/logo-grailjob.svg" alt="GrailJob logo" class="hero-logo hero-logo--wide">
      <h1 class="hero-title hero-title--search">Quel job voulez-vous suivre ?</h1>
      <p class="hero-description">
        Recherchez une entreprise existante ou démarrez une nouvelle candidature.
      </p>

      <div class="search-toolbar">
        <mat-form-field appearance="outline" class="full-width">
          <mat-label>Nom de l'entreprise</mat-label>
          <input matInput [formControl]="searchControl" placeholder="Ex. OpenAI, Alan, Back Market">
        </mat-form-field>

        <button mat-flat-button color="primary" (click)="createNew()">
          Nouvelle candidature
        </button>

        <button mat-button (click)="goToList()">
          Voir tout
        </button>
      </div>

      @if (results().length > 0) {
        <div class="results-list">
          @for (company of results(); track company) {
            <button type="button" class="result-item" (click)="openCompany(company)">
              <span>{{ company }}</span>
              <span class="result-item-link-hint">Voir les candidatures</span>
            </button>
          }
        </div>
      } @else if (searchControl.value.length >= 3) {
        <div class="results-list">
          <div class="result-item result-item--static">
            <span>Aucun résultat. Vous pouvez créer une nouvelle fiche.</span>
          </div>
        </div>
      }
    </section>
  `,
  styleUrls: ['./company-search-page.component.scss']
})
export class CompanySearchPageComponent {
  private readonly trackedJobService = inject(TrackedJobService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  protected readonly searchControl = new FormControl<string>('', { nonNullable: true });
  protected readonly results = signal<string[]>([]);

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
      switchMap((query) => this.trackedJobService.searchCompanies(query)),
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

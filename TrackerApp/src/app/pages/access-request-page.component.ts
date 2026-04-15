import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { take } from 'rxjs';
import { AccessRequestService } from '../core/access-request.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="auth-columns">
      <div class="hero-card">
        <div class="logo-lockup">
          <img src="assets/logo-grailjob.svg" alt="GrailJob logo" class="hero-logo hero-logo--compact">
          <div>
            <h1 class="hero-title auth-title">Demande d'accès</h1>
            <p class="hero-description">
              Décrivez votre besoin. Un administrateur pourra créer votre compte après validation.
            </p>
          </div>
        </div>
      </div>

      <mat-card class="surface-card">
        <h2 class="section-title">Créer une demande</h2>
        <p class="section-subtitle">Le formulaire est public.</p>

        <form [formGroup]="form" (ngSubmit)="submit()" class="form-section">
          <div class="grid-2">
            <mat-form-field appearance="outline" class="full-width">
              <mat-label>Prénom</mat-label>
              <input matInput formControlName="firstName">
            </mat-form-field>

            <mat-form-field appearance="outline" class="full-width">
              <mat-label>Nom</mat-label>
              <input matInput formControlName="lastName">
            </mat-form-field>
          </div>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Email professionnel</mat-label>
            <input matInput type="email" formControlName="email">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Entreprise</mat-label>
            <input matInput formControlName="companyName">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Motif</mat-label>
            <textarea matInput rows="5" formControlName="reason"></textarea>
          </mat-form-field>

          @if (successMessage()) {
            <p class="feedback-message feedback-message--success">{{ successMessage() }}</p>
          }

          @if (errorMessage()) {
            <p class="feedback-message feedback-message--error">{{ errorMessage() }}</p>
          }

          <div class="inline-actions">
            <button mat-flat-button color="primary" type="submit" [disabled]="form.invalid || loading()">
              Envoyer
            </button>
            <a mat-button routerLink="/login">Retour à la connexion</a>
          </div>
        </form>
      </mat-card>
    </div>
  `,
  styleUrls: ['./access-request-page.component.scss']
})
export class AccessRequestPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(AccessRequestService);

  protected readonly loading = signal(false);
  protected readonly successMessage = signal('');
  protected readonly errorMessage = signal('');

  protected readonly form = this.fb.nonNullable.group({
    firstName: [''],
    lastName: [''],
    email: ['', [Validators.required, Validators.email]],
    companyName: ['', [Validators.required]],
    reason: ['', [Validators.required, Validators.minLength(20)]]
  });

  protected submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.successMessage.set('');
    this.errorMessage.set('');

    this.service.submit(this.form.getRawValue()).pipe(take(1)).subscribe({
      next: () => {
        this.loading.set(false);
        this.successMessage.set('Votre demande a été transmise à un administrateur.');
        this.form.reset({
          firstName: '',
          lastName: '',
          email: '',
          companyName: '',
          reason: ''
        });
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('La demande n’a pas pu être envoyée.');
      }
    });
  }
}

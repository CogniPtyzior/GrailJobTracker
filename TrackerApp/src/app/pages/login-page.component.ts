import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { take } from 'rxjs';
import { AuthService } from '../core/auth.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="auth-columns">
      <div class="hero-card">
        <div class="logo-lockup">
          <img src="assets/logo-grailjob.svg" alt="GrailJob logo" class="hero-logo hero-logo--compact">
          <div>
            <h1 class="hero-title auth-title">Connectez-vous</h1>
            <p class="hero-description">
              Retrouvez vos candidatures, vos relances et vos entretiens dans un tableau unique.
            </p>
          </div>
        </div>
      </div>

      <mat-card class="surface-card">
        <h2 class="section-title">Connexion</h2>
        <p class="section-subtitle">Utilisez vos identifiants GrailJob.</p>

        <form [formGroup]="form" (ngSubmit)="submit()" class="form-section">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Email</mat-label>
            <input matInput type="email" formControlName="email">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Mot de passe</mat-label>
            <input matInput type="password" formControlName="password">
          </mat-form-field>

          @if (errorMessage()) {
            <p class="feedback-message feedback-message--error">{{ errorMessage() }}</p>
          }

          <div class="inline-actions login-actions">
            <button mat-flat-button color="primary" type="submit" [disabled]="form.invalid || loading()">
              Se connecter
            </button>
            <a mat-button routerLink="/access-request" class="login-request-link">Demander un accès</a>
          </div>
        </form>
      </mat-card>
    </div>
  `,
  styleUrls: ['./login-page.component.scss']
})
export class LoginPageComponent {
  private readonly fb = inject(FormBuilder);
  private readonly authService = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly loading = signal(false);
  protected readonly errorMessage = signal('');

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required]]
  });

  protected submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.loading.set(true);
    this.errorMessage.set('');

    this.authService.login(this.form.getRawValue()).pipe(take(1)).subscribe({
      next: () => {
        this.loading.set(false);
        void this.router.navigate(['/dashboard']);
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('Identifiants invalides.');
      }
    });
  }
}

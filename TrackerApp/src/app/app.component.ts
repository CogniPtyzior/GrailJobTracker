import { Component, computed, inject, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { take } from 'rxjs';
import { MatToolbarModule } from '@angular/material/toolbar';
import { MatButtonModule } from '@angular/material/button';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { AuthService } from './core/auth.service';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatToolbarModule, MatButtonModule, MatProgressSpinnerModule],
  template: `
    @if (ready()) {
      <div class="page-shell">
        <div class="content-frame">
          @if (shouldShowShell()) {
            <div class="app-shell-toolbar">
              <div class="brand">
                <img class="brand-logo" src="assets/logo-grailjob.svg" alt="GrailJob logo">
                <div>
                  <div class="app-brand-title">GrailJob Tracker</div>
                  <div class="app-brand-subtitle">Suivi des candidatures</div>
                </div>
              </div>

              <div class="spacer"></div>

              <a mat-button routerLink="/dashboard" routerLinkActive="mat-mdc-button-base">Accueil</a>
              <a mat-button routerLink="/tracked-jobs" routerLinkActive="mat-mdc-button-base">Mes candidatures</a>
              @if (authService.isAdmin()) {
                <a mat-button routerLink="/admin" routerLinkActive="mat-mdc-button-base">Admin</a>
              }

              @if (authService.isAuthenticated()) {
                <button mat-flat-button class="avatar-button" (click)="logout()">
                  <img src="assets/avatar-placeholder.svg" alt="Avatar">
                </button>
              }
            </div>
          }

          <router-outlet />
        </div>
      </div>
    } @else {
      <div class="page-shell page-shell--centered">
        <mat-spinner diameter="52"></mat-spinner>
      </div>
    }
  `,
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  private readonly readySignal = signal(false);
  protected readonly ready = computed(() => this.readySignal());


  public constructor() {
    this.authService.initialize().pipe(take(1)).subscribe({
      next: () => this.readySignal.set(true),
      error: () => this.readySignal.set(true)
    });
  }

  protected shouldShowShell(): boolean {
    return !['/login', '/access-request'].includes(this.router.url);
  }

  protected logout(): void {
    this.authService.logout().pipe(take(1)).subscribe({
      next: () => {
        void this.router.navigate(['/login']);
      },
      error: () => {
        void this.router.navigate(['/login']);
      }
    });
  }
}

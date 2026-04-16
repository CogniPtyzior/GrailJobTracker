import { Component, computed, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterOutlet } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { filter, take } from 'rxjs/operators';
import { AuthService } from './core/auth.service';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, MatButtonModule, MatProgressSpinnerModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  protected readonly ready = signal(false);
  private readonly currentUrl = signal(this.router.url);

  protected readonly shouldShowShell = computed(() => {
    const url = this.currentUrl();

    return !url.startsWith('/login') && !url.startsWith('/access-request');
  });
  protected readonly isDashboardActive = computed(() => this.currentUrl() === '/dashboard');
  protected readonly isTrackedJobsActive = computed(() => this.currentUrl().startsWith('/tracked-jobs'));
  protected readonly isAdminActive = computed(() => this.currentUrl().startsWith('/admin'));

  public constructor() {
    this.authService.initialize().subscribe({ next: () => this.ready.set(true), error: () => this.ready.set(true) });
    this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe((event) => {
      this.currentUrl.set((event as NavigationEnd).urlAfterRedirects);
    });
  }

  protected logout(): void {
    this.authService.logout().pipe(take(1)).subscribe({
      next: () => {
        void this.router.navigate(['/login']);
      },
      error: () => {
        this.authService.clearSession();
        void this.router.navigate(['/login']);
      }
    });
  }
}

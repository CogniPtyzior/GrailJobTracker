import { Component, computed, inject, signal } from '@angular/core';
import { NavigationEnd, Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { filter } from 'rxjs/operators';
import { AuthService } from './core/auth.service';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';

@Component({
  selector: 'app-root',
  standalone: true,
  imports: [RouterOutlet, RouterLink, RouterLinkActive, MatButtonModule, MatProgressSpinnerModule],
  templateUrl: './app.component.html',
  styleUrls: ['./app.component.scss']
})
export class AppComponent {
  protected readonly authService = inject(AuthService);
  private readonly router = inject(Router);
  protected readonly ready = signal(false);

  protected readonly shouldShowShell = computed(() => !this.router.url.startsWith('/login') && !this.router.url.startsWith('/access-request'));

  public constructor() {
    this.authService.initialize().subscribe({ next: () => this.ready.set(true), error: () => this.ready.set(true) });
    this.router.events.pipe(filter((event) => event instanceof NavigationEnd)).subscribe(() => this.ready.update((value) => value));
  }

  protected logout(): void {
    this.authService.logout();
    void this.router.navigate(['/login']);
  }
}

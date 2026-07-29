import { TestBed } from '@angular/core/testing';
import { Router, UrlTree } from '@angular/router';
import { firstValueFrom, of } from 'rxjs';
import { describe, expect, it, vi } from 'vitest';

import { authGuard } from './auth.guard';
import { AuthService } from './auth.service';

describe('authGuard', () => {
  it('redirects anonymous users to login when session initialization fails', async () => {
    const loginTree = {} as UrlTree;
    const authService = {
      isAuthenticated: vi.fn(() => false),
      initializeOnce: vi.fn(() => of(null))
    };
    const router = {
      createUrlTree: vi.fn(() => loginTree)
    };

    TestBed.configureTestingModule({
      providers: [
        { provide: AuthService, useValue: authService },
        { provide: Router, useValue: router }
      ]
    });

    const guardResult = TestBed.runInInjectionContext(() => authGuard({} as never, {} as never));
    const result = guardResult instanceof Promise || typeof guardResult === 'boolean'
      ? await guardResult
      : await firstValueFrom(guardResult);

    expect(result).toBe(loginTree);
    expect(router.createUrlTree).toHaveBeenCalledWith(['/login']);

    TestBed.resetTestingModule();
  });
});

import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { catchError, map, of } from 'rxjs';
import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.isAuthenticated()) {
    return true;
  }

  return authService.initializeOnce().pipe(
    map((user) => user ? true : router.createUrlTree(['/login'])),
    catchError(() => of(router.createUrlTree(['/login'])))
  );
};

export const adminGuard: CanActivateFn = () => {
  const authService = inject(AuthService);
  const router = inject(Router);

  if (authService.isAuthenticated() && authService.isAdmin()) {
    return true;
  }

  return authService.initializeOnce().pipe(
    map((user) => {
      if (user && authService.isAdmin()) {
        return true;
      }

      return router.createUrlTree(['/tracked-jobs']);
    }),
    catchError(() => of(router.createUrlTree(['/tracked-jobs'])))
  );
};
import { computed, inject, Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { BehaviorSubject, Observable, catchError, map, of, tap } from 'rxjs';
import { API_BASE_URL } from './api.config';
import { AuthResponse, LoginPayload, User } from '../models/auth.models';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly currentUserSignal = signal<User | null>(null);
  private readonly initializedSubject = new BehaviorSubject<boolean>(false);

  readonly user = computed(() => this.currentUserSignal());
  readonly isAdmin = computed(() => this.currentUserSignal()?.roles.includes('ROLE_ADMIN') ?? false);
  readonly isAuthenticated = computed(() => this.currentUserSignal() !== null);
  readonly initialized$ = this.initializedSubject.asObservable();

  initialize(): Observable<User | null> {
    return this.http.get<AuthResponse>(`${API_BASE_URL}/auth/me`).pipe(
      map((response) => response.user),
      tap((user) => {
        this.currentUserSignal.set(user);
        this.initializedSubject.next(true);
      }),
      catchError(() => {
        this.currentUserSignal.set(null);
        this.initializedSubject.next(true);
        return of(null);
      })
    );
  }

  login(payload: LoginPayload): Observable<User> {
    return this.http.post<AuthResponse>(`${API_BASE_URL}/auth/login`, payload).pipe(
      map((response) => response.user),
      tap((user) => this.currentUserSignal.set(user))
    );
  }

  logout(): Observable<void> {
    return this.http.post<void>(`${API_BASE_URL}/auth/logout`, {}).pipe(
      tap(() => this.currentUserSignal.set(null))
    );
  }

  clearSession(): void {
    this.currentUserSignal.set(null);
  }
}

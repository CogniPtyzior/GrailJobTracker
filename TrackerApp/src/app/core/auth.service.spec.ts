import { TestBed } from '@angular/core/testing';
import { provideHttpClient } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { afterEach, describe, expect, it } from 'vitest';

import { AuthService } from './auth.service';
import { User } from '../models/auth.models';

describe('AuthService', () => {
  afterEach(() => {
    TestBed.inject(HttpTestingController).verify();
    TestBed.resetTestingModule();
  });

  it('stores the current user after login', () => {
    const { authService, http } = setup();
    const user = testUser();

    authService.login({ email: user.email, password: 'Password1!' }).subscribe((loggedUser) => {
      expect(loggedUser).toEqual(user);
    });

    http.expectOne('/api/auth/login').flush({ user });

    expect(authService.user()).toEqual(user);
    expect(authService.isAuthenticated()).toBe(true);
  });

  it('shares concurrent initializeOnce requests', () => {
    const { authService, http } = setup();
    const user = testUser();

    authService.initializeOnce().subscribe();
    authService.initializeOnce().subscribe();

    http.expectOne('/api/auth/me').flush({ user });

    expect(http.match('/api/auth/me')).toHaveLength(0);
    expect(authService.user()).toEqual(user);
  });
});

function setup(): { authService: AuthService; http: HttpTestingController } {
  TestBed.configureTestingModule({
    providers: [
      AuthService,
      provideHttpClient(),
      provideHttpClientTesting()
    ]
  });

  return {
    authService: TestBed.inject(AuthService),
    http: TestBed.inject(HttpTestingController)
  };
}

function testUser(): User {
  return {
    id: 'user-id',
    email: 'john@example.com',
    firstName: 'John',
    lastName: 'Doe',
    roles: ['ROLE_USER'],
    isActive: true,
    createdAt: '2026-04-01T00:00:00+00:00',
    lastLoginAt: null
  };
}

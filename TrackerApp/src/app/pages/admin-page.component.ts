import { Component, computed, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { take } from 'rxjs';
import { AccessRequestService } from '../core/access-request.service';
import { AdminUserPayload, AdminUserService } from '../core/admin-user.service';
import { AccessRequestItem } from '../models/access-request.models';
import { User } from '../models/auth.models';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { AuthService } from '../core/auth.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatCardModule, MatCheckboxModule, MatFormFieldModule, MatInputModule, MatProgressSpinnerModule],
  templateUrl: './admin-page.component.html',
  styleUrls: ['./admin-page.component.scss']
})
export class AdminPageComponent {
  private readonly userService = inject(AdminUserService);
  private readonly accessRequestService = inject(AccessRequestService);
  private readonly fb = inject(FormBuilder);
  protected readonly authService = inject(AuthService);

  protected readonly users = signal<User[]>([]);
  protected readonly requests = signal<AccessRequestItem[]>([]);
  protected readonly userMessage = signal('');
  protected readonly requestMessage = signal('');
  protected readonly editingUserId = signal<string | null>(null);
  protected readonly editingRequestId = signal<string | null>(null);
  protected readonly usersLoading = signal(false);
  protected readonly requestsLoading = signal(false);
  protected readonly userActionLoading = signal(false);
  protected readonly requestActionLoading = signal(false);
  protected readonly usersBusy = computed(() => this.usersLoading() || this.userActionLoading());
  protected readonly requestsBusy = computed(() => this.requestsLoading() || this.requestActionLoading());

  protected readonly createUserForm = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
    firstName: [''],
    lastName: [''],
    isActive: [true],
    isAdmin: [false]
  });

  protected readonly editUserForm = this.fb.nonNullable.group({
    firstName: [''],
    lastName: [''],
    password: [''],
    isActive: [true],
    isAdmin: [false]
  });

  protected readonly approveRequestForm = this.fb.nonNullable.group({
    firstName: [''],
    lastName: [''],
    password: ['', [Validators.required, Validators.minLength(8)]]
  });

  public constructor() {
    this.loadUsers();
    this.loadRequests();
  }

  protected loadUsers(): void {
    this.usersLoading.set(true);

    this.userService.list('', '', 1, 100).pipe(take(1)).subscribe({
      next: (response) => this.users.set(response.items),
      error: () => this.userMessage.set('Chargement des utilisateurs impossible.'),
      complete: () => this.usersLoading.set(false)
    });
  }

  protected loadRequests(): void {
    this.requestsLoading.set(true);

    this.accessRequestService.list('', 1, 100).pipe(take(1)).subscribe({
      next: (response) => this.requests.set(response.items),
      error: () => this.requestMessage.set('Chargement des demandes impossible.'),
      complete: () => this.requestsLoading.set(false)
    });
  }

  protected createUser(): void {
    if (this.createUserForm.invalid || this.usersBusy()) {
      return;
    }

    const payload = this.createUserForm.getRawValue();
    this.userActionLoading.set(true);

    this.userService.create(payload).pipe(take(1)).subscribe({
      next: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Utilisateur créé.');
        this.createUserForm.reset({ email: '', password: '', firstName: '', lastName: '', isActive: true, isAdmin: false });
        this.loadUsers();
      },
      error: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Création impossible.');
      }
    });
  }

  protected toggleUserEditor(id: string): void {
    const user = this.users().find((item) => item.id === id);

    if (!user) {
      return;
    }

    this.editingUserId.set(this.editingUserId() === id ? null : id);
    this.editUserForm.reset({
      firstName: user.firstName ?? '',
      lastName: user.lastName ?? '',
      password: '',
      isActive: user.isActive,
      isAdmin: user.roles.includes('ROLE_ADMIN')
    });
  }

  protected saveUser(user: User): void {
    if (this.usersBusy()) {
      return;
    }

    const raw = this.editUserForm.getRawValue();
    const payload: AdminUserPayload = {
      firstName: raw.firstName || null,
      lastName: raw.lastName || null,
      isActive: raw.isActive,
      isAdmin: raw.isAdmin
    };

    if (raw.password) {
      payload.password = raw.password;
    }

    this.userActionLoading.set(true);
    this.userService.update(user.id, payload).pipe(take(1)).subscribe({
      next: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Utilisateur mis à jour.');
        this.editingUserId.set(null);
        this.loadUsers();
      },
      error: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Mise à jour impossible.');
      }
    });
  }

  protected deleteUser(user: User): void {
    if (this.usersBusy() || !confirm(`Supprimer ${user.email} ?`)) {
      return;
    }

    this.userActionLoading.set(true);
    this.userService.remove(user.id).pipe(take(1)).subscribe({
      next: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Utilisateur supprimé.');
        this.loadUsers();
      },
      error: () => {
        this.userActionLoading.set(false);
        this.userMessage.set('Suppression impossible.');
      }
    });
  }

  protected toggleRequestEditor(id: string): void {
    const request = this.requests().find((item) => item.id === id);

    if (!request) {
      return;
    }

    this.editingRequestId.set(this.editingRequestId() === id ? null : id);
    this.approveRequestForm.reset({
      firstName: request.firstName ?? '',
      lastName: request.lastName ?? '',
      password: ''
    });
  }

  protected approveRequest(request: AccessRequestItem): void {
    if (this.approveRequestForm.invalid || this.requestsBusy()) {
      return;
    }

    this.requestActionLoading.set(true);
    this.accessRequestService.approve(request.id, this.approveRequestForm.getRawValue()).pipe(take(1)).subscribe({
      next: () => {
        this.requestActionLoading.set(false);
        this.requestMessage.set('Demande approuvée.');
        this.editingRequestId.set(null);
        this.loadRequests();
        this.loadUsers();
      },
      error: () => {
        this.requestActionLoading.set(false);
        this.requestMessage.set('Approbation impossible.');
      }
    });
  }

  protected deleteRequest(id: string): void {
    if (this.requestsBusy() || !confirm('Supprimer cette demande ?')) {
      return;
    }

    this.requestActionLoading.set(true);
    this.accessRequestService.remove(id).pipe(take(1)).subscribe({
      next: () => {
        this.requestActionLoading.set(false);
        this.requestMessage.set('Demande supprimée.');
        this.loadRequests();
      },
      error: () => {
        this.requestActionLoading.set(false);
        this.requestMessage.set('Suppression impossible.');
      }
    });
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
  }
}

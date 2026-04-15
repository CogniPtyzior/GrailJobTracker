import { Component, inject, signal } from '@angular/core';
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
import { AuthService } from '../core/auth.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, MatButtonModule, MatCardModule, MatCheckboxModule, MatFormFieldModule, MatInputModule],
  template: `
    <div class="admin-layout">
      <section class="surface-card">
        <h1 class="section-title">Administration</h1>
        <p class="section-subtitle">Gérez les comptes utilisateurs et les demandes d'accès.</p>
      </section>

      <section class="surface-card">
        <div class="section-header">
          <div>
            <h2 class="section-title">Utilisateurs</h2>
            <p class="section-subtitle">Création, activation et droits administrateur.</p>
          </div>
          <div class="spacer"></div>
          <button mat-button (click)="loadUsers()">Rafraîchir</button>
        </div>

        <form [formGroup]="createUserForm" (ngSubmit)="createUser()" class="grid-3 form-section">
          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Email</mat-label>
            <input matInput formControlName="email">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Mot de passe</mat-label>
            <input matInput formControlName="password">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Prénom</mat-label>
            <input matInput formControlName="firstName">
          </mat-form-field>

          <mat-form-field appearance="outline" class="full-width">
            <mat-label>Nom</mat-label>
            <input matInput formControlName="lastName">
          </mat-form-field>

          <label class="checkbox-label">
            <input type="checkbox" formControlName="isActive">
            Compte actif
          </label>

          <label class="checkbox-label">
            <input type="checkbox" formControlName="isAdmin">
            Admin
          </label>

          <div class="inline-actions admin-form-actions">
            <button mat-flat-button color="primary" type="submit" [disabled]="createUserForm.invalid">Créer l'utilisateur</button>
          </div>
        </form>

        @if (userMessage()) {
          <p class="feedback-message admin-message">{{ userMessage() }}</p>
        }

        <div class="results-list results-list--spaced">
          @for (user of users(); track user.id) {
            <div class="table-row-card">
              <div class="row-summary user-summary-grid">
                <div>
                  <div class="summary-title">{{ user.email }}</div>
                  <div class="summary-muted">{{ user.firstName || '—' }} {{ user.lastName || '' }}</div>
                </div>
                <div>
                  <div>{{ user.roles.join(', ') }}</div>
                  <div class="summary-muted">Dernière connexion : {{ user.lastLoginAt ? formatDate(user.lastLoginAt) : 'Jamais' }}</div>
                </div>
                <div class="inline-actions row-actions">
                  <button mat-stroked-button (click)="toggleUserEditor(user.id)">Éditer</button>
                  <button mat-button (click)="deleteUser(user)" [disabled]="authService.user()?.id === user.id">Supprimer</button>
                </div>
              </div>

              @if (editingUserId() === user.id) {
                <div class="row-details">
                  <form class="grid-3" [formGroup]="editUserForm">
                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Prénom</mat-label>
                      <input matInput formControlName="firstName">
                    </mat-form-field>

                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Nom</mat-label>
                      <input matInput formControlName="lastName">
                    </mat-form-field>

                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Nouveau mot de passe</mat-label>
                      <input matInput formControlName="password">
                    </mat-form-field>

                    <label class="checkbox-label">
                      <input type="checkbox" formControlName="isActive">
                      Actif
                    </label>

                    <label class="checkbox-label">
                      <input type="checkbox" formControlName="isAdmin">
                      Admin
                    </label>

                    <div class="inline-actions">
                      <button mat-flat-button color="primary" type="button" (click)="saveUser(user)">Enregistrer</button>
                    </div>
                  </form>
                </div>
              }
            </div>
          }
        </div>
      </section>

      <section class="surface-card">
        <div class="section-header">
          <div>
            <h2 class="section-title">Demandes d'accès</h2>
            <p class="section-subtitle">Validation manuelle avec attribution d'un mot de passe initial.</p>
          </div>
          <div class="spacer"></div>
          <button mat-button (click)="loadRequests()">Rafraîchir</button>
        </div>

        @if (requestMessage()) {
          <p class="feedback-message admin-message">{{ requestMessage() }}</p>
        }

        <div class="results-list results-list--spaced">
          @for (request of requests(); track request.id) {
            <div class="table-row-card">
              <div class="row-summary request-summary-grid">
                <div>
                  <div class="summary-title">{{ request.email }}</div>
                  <div class="summary-muted">{{ request.companyName }} · {{ request.firstName || '—' }} {{ request.lastName || '' }}</div>
                </div>
                <div>
                  <div>{{ formatDate(request.createdAt) }}</div>
                  <div class="summary-muted">{{ request.reason }}</div>
                </div>
                <div class="inline-actions row-actions">
                  <button mat-stroked-button (click)="toggleRequestEditor(request.id)">Approuver</button>
                  <button mat-button (click)="deleteRequest(request.id)">Supprimer</button>
                </div>
              </div>

              @if (editingRequestId() === request.id) {
                <div class="row-details">
                  <form [formGroup]="approveRequestForm" class="grid-3">
                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Prénom</mat-label>
                      <input matInput formControlName="firstName">
                    </mat-form-field>

                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Nom</mat-label>
                      <input matInput formControlName="lastName">
                    </mat-form-field>

                    <mat-form-field appearance="outline" class="full-width">
                      <mat-label>Mot de passe initial</mat-label>
                      <input matInput formControlName="password">
                    </mat-form-field>
                  </form>

                  <div class="inline-actions">
                    <button mat-flat-button color="primary" (click)="approveRequest(request)">Valider la demande</button>
                  </div>
                </div>
              }
            </div>
          } @empty {
            <div class="surface-card">
              <p class="section-subtitle">Aucune demande en attente.</p>
            </div>
          }
        </div>
      </section>
    </div>
  `,
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
    this.userService.list('', '', 1, 100).pipe(take(1)).subscribe((response) => this.users.set(response.items));
  }

  protected loadRequests(): void {
    this.accessRequestService.list('', 1, 100).pipe(take(1)).subscribe((response) => this.requests.set(response.items));
  }

  protected createUser(): void {
    if (this.createUserForm.invalid) {
      return;
    }

    const payload = this.createUserForm.getRawValue();
    this.userService.create(payload).pipe(take(1)).subscribe({
      next: () => {
        this.userMessage.set('Utilisateur créé.');
        this.createUserForm.reset({ email: '', password: '', firstName: '', lastName: '', isActive: true, isAdmin: false });
        this.loadUsers();
      },
      error: () => this.userMessage.set('Création impossible.')
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

    this.userService.update(user.id, payload).pipe(take(1)).subscribe({
      next: () => {
        this.userMessage.set('Utilisateur mis à jour.');
        this.editingUserId.set(null);
        this.loadUsers();
      },
      error: () => this.userMessage.set('Mise à jour impossible.')
    });
  }

  protected deleteUser(user: User): void {
    if (!confirm(`Supprimer ${user.email} ?`)) {
      return;
    }

    this.userService.remove(user.id).pipe(take(1)).subscribe({
      next: () => {
        this.userMessage.set('Utilisateur supprimé.');
        this.loadUsers();
      },
      error: () => this.userMessage.set('Suppression impossible.')
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
    if (this.approveRequestForm.invalid) {
      return;
    }

    this.accessRequestService.approve(request.id, this.approveRequestForm.getRawValue()).pipe(take(1)).subscribe({
      next: () => {
        this.requestMessage.set('Demande approuvée.');
        this.editingRequestId.set(null);
        this.loadRequests();
        this.loadUsers();
      },
      error: () => this.requestMessage.set('Approbation impossible.')
    });
  }

  protected deleteRequest(id: string): void {
    if (!confirm('Supprimer cette demande ?')) {
      return;
    }

    this.accessRequestService.remove(id).pipe(take(1)).subscribe({
      next: () => {
        this.requestMessage.set('Demande supprimée.');
        this.loadRequests();
      },
      error: () => this.requestMessage.set('Suppression impossible.')
    });
  }

  protected formatDate(value: string): string {
    return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
  }
}

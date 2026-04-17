import { Component, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { MatButtonModule } from '@angular/material/button';
import { MatCardModule } from '@angular/material/card';
import { MatFormFieldModule } from '@angular/material/form-field';
import { MatInputModule } from '@angular/material/input';
import { MatProgressSpinnerModule } from '@angular/material/progress-spinner';
import { take } from 'rxjs';
import { AccessRequestService } from '../core/access-request.service';

@Component({
  standalone: true,
  imports: [ReactiveFormsModule, RouterLink, MatButtonModule, MatCardModule, MatFormFieldModule, MatInputModule, MatProgressSpinnerModule],
  templateUrl: './access-request-page.component.html',
  styleUrls: ['./access-request-page.component.scss']
})
export class AccessRequestPageComponent {
  protected readonly reasonMinLength = 20;
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(AccessRequestService);

  protected readonly loading = signal(false);
  protected readonly successMessage = signal('');
  protected readonly errorMessage = signal('');

  protected readonly form = this.fb.nonNullable.group({
    firstName: [''],
    lastName: [''],
    email: ['', [Validators.required, Validators.email]],
    companyName: ['', [Validators.required]],
    reason: ['', [Validators.required, Validators.minLength(this.reasonMinLength)]]
  });

  protected get reasonLength(): number {
    return this.form.controls.reason.value.trim().length;
  }

  protected get remainingReasonCharacters(): number {
    return Math.max(this.reasonMinLength - this.reasonLength, 0);
  }

  protected submit(): void {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.loading.set(true);
    this.successMessage.set('');
    this.errorMessage.set('');

    this.service.submit(this.form.getRawValue()).pipe(take(1)).subscribe({
      next: () => {
        this.loading.set(false);
        this.successMessage.set('Votre demande a été transmise à un administrateur.');
        this.form.reset({
          firstName: '',
          lastName: '',
          email: '',
          companyName: '',
          reason: ''
        });
      },
      error: () => {
        this.loading.set(false);
        this.errorMessage.set('La demande n’a pas pu être envoyée.');
      }
    });
  }
}

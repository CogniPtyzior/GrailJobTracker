import { Routes } from '@angular/router';
import { authGuard, adminGuard } from './core/auth.guard';
import { LoginPageComponent } from './pages/login-page.component';
import { AccessRequestPageComponent } from './pages/access-request-page.component';
import { CompanySearchPageComponent } from './pages/company-search-page.component';
import { TrackedJobsPageComponent } from './pages/tracked-jobs-page.component';
import { TrackedJobFormPageComponent } from './pages/tracked-job-form-page.component';
import { AdminPageComponent } from './pages/admin-page.component';

export const routes: Routes = [
  { path: '', pathMatch: 'full', redirectTo: 'tracked-jobs' },
  { path: 'login', component: LoginPageComponent },
  { path: 'access-request', component: AccessRequestPageComponent },
  { path: 'dashboard', component: CompanySearchPageComponent, canActivate: [authGuard] },
  { path: 'tracked-jobs', component: TrackedJobsPageComponent, canActivate: [authGuard] },
  { path: 'tracked-jobs/new', component: TrackedJobFormPageComponent, canActivate: [authGuard] },
  { path: 'tracked-jobs/:id', component: TrackedJobFormPageComponent, canActivate: [authGuard] },
  { path: 'admin', component: AdminPageComponent, canActivate: [adminGuard] },
  { path: '**', redirectTo: 'tracked-jobs' }
];

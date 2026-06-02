import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login.component').then(m => m.LoginComponent)
  },
  {
    path: 'register',
    loadComponent: () => import('./features/auth/register/register.component').then(m => m.RegisterComponent)
  },
  {
    path: '',
    canActivate: [authGuard],
    children: [
      { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard.component').then(m => m.DashboardComponent)
      },
      {
        path: 'clients',
        loadComponent: () => import('./features/clients/client-list/client-list').then(m => m.ClientList)
      },
      {
        path: 'clients/new',
        loadComponent: () => import('./features/clients/client-form/client-form').then(m => m.ClientForm)
      },
      {
        path: 'clients/:id/edit',
        loadComponent: () => import('./features/clients/client-form/client-form').then(m => m.ClientForm)
      },
      {
        path: 'invoices',
        loadComponent: () => import('./features/invoices/invoice-list/invoice-list').then(m => m.InvoiceList)
      },
      {
        path: 'invoices/new',
        loadComponent: () => import('./features/invoices/invoice-form/invoice-form').then(m => m.InvoiceForm)
      },
      {
        path: 'invoices/:id',
        loadComponent: () => import('./features/invoices/invoice-detail/invoice-detail').then(m => m.InvoiceDetail)
      },
      {
        path: 'quotations',
        loadComponent: () => import('./features/quotations/quotation-list/quotation-list').then(m => m.QuotationList)
      },
      {
        path: 'quotations/new',
        loadComponent: () => import('./features/quotations/quotation-form/quotation-form').then(m => m.QuotationForm)
      },
      {
        path: 'quotations/:id',
        loadComponent: () => import('./features/quotations/quotation-detail/quotation-detail').then(m => m.QuotationDetail)
      },
    ]
  },
  { path: '**', redirectTo: 'dashboard' }
];

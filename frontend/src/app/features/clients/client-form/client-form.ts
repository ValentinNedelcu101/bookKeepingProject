import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { ClientService } from '../../../core/services/client.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-client-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, Navbar],
  templateUrl: './client-form.html',
  styleUrl: './client-form.scss',
})
export class ClientForm implements OnInit {
  private fb = inject(FormBuilder);
  private clientService = inject(ClientService);
  private route = inject(ActivatedRoute);
  private router = inject(Router);
  private toast = inject(ToastService);

  editId: number | null = null;
  saving = false;
  loadingClient = false;

  form = this.fb.group({
    name: ['', Validators.required],
    contact_email: [''],
    phone: [''],
    billing_address: [''],
    tax_number: [''],
  });

  ngOnInit() {
    const id = this.route.snapshot.paramMap.get('id');
    if (id) {
      this.editId = +id;
      this.loadingClient = true;
      this.clientService.get(this.editId).subscribe({
        next: client => {
          this.form.patchValue({
            name: client.name,
            contact_email: client.contact_email ?? '',
            phone: client.phone ?? '',
            billing_address: client.billing_address ?? '',
            tax_number: client.tax_number ?? '',
          });
          this.loadingClient = false;
        },
        error: () => (this.loadingClient = false),
      });
    }
  }

  get isEdit() {
    return this.editId !== null;
  }

  onSubmit() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    this.saving = true;
    const data = this.form.value as any;
    const action = this.isEdit
      ? this.clientService.update(this.editId!, data)
      : this.clientService.create(data);

    action.subscribe({
      next: () => {
        this.toast.success(this.isEdit ? 'Client updated.' : 'Client created.');
        this.router.navigate(['/clients']);
      },
      error: () => {
        this.toast.error('Something went wrong. Please try again.');
        this.saving = false;
      },
    });
  }
}

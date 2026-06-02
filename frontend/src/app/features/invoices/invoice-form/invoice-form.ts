import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormArray, Validators, AbstractControl } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { ClientService, Client } from '../../../core/services/client.service';
import { InvoiceService } from '../../../core/services/invoice.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-invoice-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, Navbar],
  templateUrl: './invoice-form.html',
  styleUrl: './invoice-form.scss',
})
export class InvoiceForm implements OnInit {
  private fb = inject(FormBuilder);
  private clientService = inject(ClientService);
  private invoiceService = inject(InvoiceService);
  private router = inject(Router);
  private toast = inject(ToastService);

  clients: Client[] = [];
  saving = false;

  form = this.fb.group({
    invoice_number: ['', Validators.required],
    client_id: [null as number | null, Validators.required],
    issue_date: [this.today(), Validators.required],
    due_date: [''],
    notes: [''],
    items: this.fb.array([this.newItemGroup()]),
  });

  ngOnInit() {
    this.clientService.list().subscribe(clients => (this.clients = clients));
    this.invoiceService.list().subscribe(invoices => {
      const n = invoices.length + 1;
      this.form.patchValue({ invoice_number: `INV-${String(n).padStart(3, '0')}` });
    });
  }

  get items(): FormArray {
    return this.form.get('items') as FormArray;
  }

  newItemGroup() {
    return this.fb.group({
      description: ['', Validators.required],
      quantity: [1, [Validators.required, Validators.min(1)]],
      unit_price: ['', [Validators.required, Validators.min(0)]],
    });
  }

  addItem() {
    this.items.push(this.newItemGroup());
  }

  removeItem(i: number) {
    if (this.items.length > 1) this.items.removeAt(i);
  }

  lineTotal(ctrl: AbstractControl): number {
    const v = ctrl.value;
    return +(parseFloat(v.quantity || 0) * parseFloat(v.unit_price || 0)).toFixed(2);
  }

  get subtotal(): number {
    return this.items.controls.reduce((sum, c) => sum + this.lineTotal(c), 0);
  }

  onSubmit() {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      this.toast.error('Please fill in all required fields.');
      return;
    }
    this.saving = true;

    const v = this.form.value;
    const payload = {
      invoice_number: v.invoice_number,
      client_id: v.client_id,
      issue_date: v.issue_date,
      due_date: v.due_date || undefined,
      notes: v.notes || undefined,
      items: this.items.controls.map(c => ({
        description: c.value.description,
        quantity: +c.value.quantity,
        unit_price: parseFloat(c.value.unit_price).toFixed(2),
        line_total: this.lineTotal(c).toFixed(2),
      })),
    };

    this.invoiceService.create(payload).subscribe({
      next: inv => {
        this.toast.success(`Invoice ${inv.invoice_number} created.`);
        this.router.navigate(['/invoices', inv.id]);
      },
      error: () => {
        this.toast.error('Could not create invoice. Please try again.');
        this.saving = false;
      },
    });
  }

  private today(): string {
    return new Date().toISOString().split('T')[0];
  }
}

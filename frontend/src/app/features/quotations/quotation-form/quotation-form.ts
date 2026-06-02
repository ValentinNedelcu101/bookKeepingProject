import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ReactiveFormsModule, FormBuilder, FormArray, Validators, AbstractControl } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { ClientService, Client } from '../../../core/services/client.service';
import { QuotationService } from '../../../core/services/quotation.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-quotation-form',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink, Navbar],
  templateUrl: './quotation-form.html',
  styleUrl: './quotation-form.scss',
})
export class QuotationForm implements OnInit {
  private fb = inject(FormBuilder);
  private clientService = inject(ClientService);
  private quotationService = inject(QuotationService);
  private router = inject(Router);
  private toast = inject(ToastService);

  clients: Client[] = [];
  saving = false;

  form = this.fb.group({
    quotation_number: ['', Validators.required],
    client_id: [null as number | null, Validators.required],
    issue_date: [this.today(), Validators.required],
    valid_until: [''],
    notes: [''],
    items: this.fb.array([this.newItemGroup()]),
  });

  ngOnInit() {
    this.clientService.list().subscribe(clients => (this.clients = clients));
    this.quotationService.list().subscribe(quotations => {
      const n = quotations.length + 1;
      this.form.patchValue({ quotation_number: `QUO-${String(n).padStart(3, '0')}` });
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
      quotation_number: v.quotation_number,
      client_id: v.client_id,
      issue_date: v.issue_date,
      valid_until: v.valid_until || undefined,
      notes: v.notes || undefined,
      items: this.items.controls.map(c => ({
        description: c.value.description,
        quantity: +c.value.quantity,
        unit_price: parseFloat(c.value.unit_price).toFixed(2),
        line_total: this.lineTotal(c).toFixed(2),
      })),
    };

    this.quotationService.create(payload).subscribe({
      next: q => {
        this.toast.success(`Quotation ${q.quotation_number} created.`);
        this.router.navigate(['/quotations', q.id]);
      },
      error: () => {
        this.toast.error('Could not create quotation. Please try again.');
        this.saving = false;
      },
    });
  }

  private today(): string {
    return new Date().toISOString().split('T')[0];
  }
}

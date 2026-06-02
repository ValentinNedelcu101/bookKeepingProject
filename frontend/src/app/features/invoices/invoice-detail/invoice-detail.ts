import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { InvoiceService, Invoice } from '../../../core/services/invoice.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-invoice-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, Navbar],
  templateUrl: './invoice-detail.html',
  styleUrl: './invoice-detail.scss',
})
export class InvoiceDetail implements OnInit {
  invoice: Invoice | null = null;
  loading = true;
  statusChanging = false;
  deleting = false;
  confirmDeleteVisible = false;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private invoiceService: InvoiceService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    const id = +this.route.snapshot.paramMap.get('id')!;
    this.invoiceService.get(id).subscribe({
      next: inv => {
        this.invoice = inv;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  clientName(): string {
    if (!this.invoice) return '';
    return typeof this.invoice.client === 'string'
      ? this.invoice.client
      : this.invoice.client.name;
  }

  changeStatus(status: string) {
    if (!this.invoice) return;
    this.statusChanging = true;
    this.invoiceService.changeStatus(this.invoice.id, status).subscribe({
      next: res => {
        this.invoice!.status = res.status as Invoice['status'];
        this.statusChanging = false;
        const labels: Record<string, string> = {
          sent: 'Invoice marked as sent.',
          paid: 'Invoice marked as paid.',
          cancelled: 'Invoice cancelled.',
        };
        this.toast.success(labels[status] ?? 'Status updated.');
      },
      error: () => {
        this.toast.error('Could not update status.');
        this.statusChanging = false;
      },
    });
  }

  doDelete() {
    if (!this.invoice) return;
    this.deleting = true;
    this.invoiceService.delete(this.invoice.id).subscribe({
      next: () => {
        this.toast.success('Invoice deleted.');
        this.router.navigate(['/invoices']);
      },
      error: () => {
        this.toast.error('Could not delete invoice.');
        this.deleting = false;
        this.confirmDeleteVisible = false;
      },
    });
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-700',
      sent: 'bg-blue-100 text-blue-700',
      paid: 'bg-green-100 text-green-700',
      cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
  }
}

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { QuotationService, Quotation } from '../../../core/services/quotation.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-quotation-detail',
  standalone: true,
  imports: [CommonModule, RouterLink, Navbar],
  templateUrl: './quotation-detail.html',
  styleUrl: './quotation-detail.scss',
})
export class QuotationDetail implements OnInit {
  quotation: Quotation | null = null;
  loading = true;
  statusChanging = false;
  converting = false;
  deleting = false;
  confirmDeleteVisible = false;
  convertedInvoiceId: number | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private quotationService: QuotationService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    const id = +this.route.snapshot.paramMap.get('id')!;
    this.quotationService.get(id).subscribe({
      next: q => {
        this.quotation = q;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  clientName(): string {
    if (!this.quotation) return '';
    return typeof this.quotation.client === 'string'
      ? this.quotation.client
      : this.quotation.client.name;
  }

  changeStatus(status: string) {
    if (!this.quotation) return;
    this.statusChanging = true;
    this.quotationService.changeStatus(this.quotation.id, status).subscribe({
      next: res => {
        this.quotation!.status = res.status as Quotation['status'];
        this.statusChanging = false;
        const labels: Record<string, string> = {
          sent: 'Quotation marked as sent.',
          accepted: 'Quotation accepted.',
          rejected: 'Quotation rejected.',
        };
        this.toast.success(labels[status] ?? 'Status updated.');
      },
      error: () => {
        this.toast.error('Could not update status.');
        this.statusChanging = false;
      },
    });
  }

  convert() {
    if (!this.quotation) return;
    this.converting = true;
    this.quotationService.convert(this.quotation.id).subscribe({
      next: res => {
        this.quotation!.status = 'converted';
        this.converting = false;
        this.convertedInvoiceId = res.invoice_id;
        this.toast.success('Converted to invoice! Redirecting…');
        setTimeout(() => this.router.navigate(['/invoices', res.invoice_id]), 1800);
      },
      error: () => {
        this.toast.error('Could not convert quotation.');
        this.converting = false;
      },
    });
  }

  doDelete() {
    if (!this.quotation) return;
    this.deleting = true;
    this.quotationService.delete(this.quotation.id).subscribe({
      next: () => {
        this.toast.success('Quotation deleted.');
        this.router.navigate(['/quotations']);
      },
      error: () => {
        this.toast.error('Could not delete quotation.');
        this.deleting = false;
        this.confirmDeleteVisible = false;
      },
    });
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-700',
      sent: 'bg-blue-100 text-blue-700',
      accepted: 'bg-green-100 text-green-700',
      rejected: 'bg-red-100 text-red-700',
      converted: 'bg-purple-100 text-purple-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-700';
  }
}

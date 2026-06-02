import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { InvoiceService, Invoice } from '../../../core/services/invoice.service';

@Component({
  selector: 'app-invoice-list',
  standalone: true,
  imports: [CommonModule, RouterLink, Navbar],
  templateUrl: './invoice-list.html',
  styleUrl: './invoice-list.scss',
})
export class InvoiceList implements OnInit {
  invoices: Invoice[] = [];
  loading = true;
  activeFilter = 'all';

  readonly filters = ['all', 'draft', 'sent', 'paid', 'cancelled'];

  constructor(private invoiceService: InvoiceService) {}

  ngOnInit() {
    this.invoiceService.list().subscribe({
      next: invoices => {
        this.invoices = invoices;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  get filtered(): Invoice[] {
    if (this.activeFilter === 'all') return this.invoices;
    return this.invoices.filter(i => i.status === this.activeFilter);
  }

  count(status: string): number {
    if (status === 'all') return this.invoices.length;
    return this.invoices.filter(i => i.status === status).length;
  }

  clientName(invoice: Invoice): string {
    return typeof invoice.client === 'string' ? invoice.client : invoice.client.name;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-600',
      sent: 'bg-blue-100 text-blue-700',
      paid: 'bg-green-100 text-green-700',
      cancelled: 'bg-red-100 text-red-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
  }
}

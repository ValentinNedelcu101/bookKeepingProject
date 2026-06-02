import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { forkJoin } from 'rxjs';
import { Navbar } from '../../shared/components/navbar/navbar';
import { ClientService } from '../../core/services/client.service';
import { InvoiceService, Invoice } from '../../core/services/invoice.service';
import { QuotationService, Quotation } from '../../core/services/quotation.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink, Navbar],
  templateUrl: './dashboard.component.html',
})
export class DashboardComponent implements OnInit {
  loading = true;

  stats = {
    clients: 0,
    invoices: 0,
    quotations: 0,
    revenue: '0.00',
    unpaid: '0.00',
  };

  recentInvoices: Invoice[] = [];
  recentQuotations: Quotation[] = [];

  constructor(
    private clientService: ClientService,
    private invoiceService: InvoiceService,
    private quotationService: QuotationService
  ) {}

  ngOnInit() {
    forkJoin([
      this.clientService.list(),
      this.invoiceService.list(),
      this.quotationService.list(),
    ]).subscribe({
      next: ([clients, invoices, quotations]) => {
        this.stats.clients = clients.length;
        this.stats.invoices = invoices.length;
        this.stats.quotations = quotations.length;

        this.stats.revenue = invoices
          .filter(i => i.status === 'paid')
          .reduce((sum, i) => sum + parseFloat(i.total || '0'), 0)
          .toFixed(2);

        this.stats.unpaid = invoices
          .filter(i => i.status === 'sent')
          .reduce((sum, i) => sum + parseFloat(i.total || '0'), 0)
          .toFixed(2);

        this.recentInvoices = invoices.slice(0, 5);
        this.recentQuotations = quotations.slice(0, 5);
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  clientName(invoice: Invoice): string {
    return typeof invoice.client === 'string' ? invoice.client : invoice.client.name;
  }

  quotationClient(q: Quotation): string {
    return typeof q.client === 'string' ? q.client : q.client.name;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-600',
      sent: 'bg-blue-100 text-blue-700',
      paid: 'bg-green-100 text-green-700',
      cancelled: 'bg-red-100 text-red-700',
      accepted: 'bg-green-100 text-green-700',
      rejected: 'bg-red-100 text-red-700',
      converted: 'bg-purple-100 text-purple-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
  }
}

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { QuotationService, Quotation } from '../../../core/services/quotation.service';

@Component({
  selector: 'app-quotation-list',
  standalone: true,
  imports: [CommonModule, RouterLink, Navbar],
  templateUrl: './quotation-list.html',
  styleUrl: './quotation-list.scss',
})
export class QuotationList implements OnInit {
  quotations: Quotation[] = [];
  loading = true;
  activeFilter = 'all';

  readonly filters = ['all', 'draft', 'sent', 'accepted', 'rejected', 'converted'];

  constructor(private quotationService: QuotationService) {}

  ngOnInit() {
    this.quotationService.list().subscribe({
      next: quotations => {
        this.quotations = quotations;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  get filtered(): Quotation[] {
    if (this.activeFilter === 'all') return this.quotations;
    return this.quotations.filter(q => q.status === this.activeFilter);
  }

  count(status: string): number {
    if (status === 'all') return this.quotations.length;
    return this.quotations.filter(q => q.status === status).length;
  }

  clientName(q: Quotation): string {
    return typeof q.client === 'string' ? q.client : q.client.name;
  }

  statusClass(status: string): string {
    const map: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-600',
      sent: 'bg-blue-100 text-blue-700',
      accepted: 'bg-green-100 text-green-700',
      rejected: 'bg-red-100 text-red-700',
      converted: 'bg-purple-100 text-purple-700',
    };
    return map[status] ?? 'bg-gray-100 text-gray-600';
  }
}

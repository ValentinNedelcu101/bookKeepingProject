import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface InvoiceItem {
  id?: number;
  description: string;
  quantity: number;
  unit_price: string;
  tax?: string | null;
  line_total?: string;
}

export interface Invoice {
  id: number;
  invoice_number: string;
  status: 'draft' | 'sent' | 'paid' | 'cancelled';
  client: { id: number; name: string } | string;
  issue_date: string;
  due_date: string | null;
  subtotal?: string;
  tax_total?: string;
  total: string;
  notes?: string | null;
  items?: InvoiceItem[];
  pdf_generated_at?: string | null;
  updated_at?: string | null;
}

@Injectable({ providedIn: 'root' })
export class InvoiceService {
  private url = `${environment.apiUrl}/invoices`;

  constructor(private http: HttpClient) {}

  list(): Observable<Invoice[]> {
    return this.http.get<Invoice[]>(this.url);
  }

  get(id: number): Observable<Invoice> {
    return this.http.get<Invoice>(`${this.url}/${id}`);
  }

  create(data: any): Observable<Invoice> {
    return this.http.post<Invoice>(this.url, data);
  }

  update(id: number, data: any): Observable<Invoice> {
    return this.http.patch<Invoice>(`${this.url}/${id}`, data);
  }

  changeStatus(id: number, status: string): Observable<{ status: string }> {
    return this.http.patch<{ status: string }>(`${this.url}/${id}/status`, { status });
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}

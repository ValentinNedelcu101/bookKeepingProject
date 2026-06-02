import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';

export interface QuotationItem {
  id?: number;
  description: string;
  quantity: number;
  unit_price: string;
  tax_rate?: string | null;
  line_total?: string;
}

export interface Quotation {
  id: number;
  quotation_number: string;
  status: 'draft' | 'sent' | 'accepted' | 'rejected' | 'converted';
  client: { id: number; name: string } | string;
  issue_date: string;
  valid_until: string | null;
  subtotal?: string;
  tax_total?: string;
  total: string;
  notes?: string | null;
  items?: QuotationItem[];
  pdf_generated_at?: string | null;
  updated_at?: string | null;
}

@Injectable({ providedIn: 'root' })
export class QuotationService {
  private url = `${environment.apiUrl}/quotations`;

  constructor(private http: HttpClient) {}

  list(): Observable<Quotation[]> {
    return this.http.get<Quotation[]>(this.url);
  }

  get(id: number): Observable<Quotation> {
    return this.http.get<Quotation>(`${this.url}/${id}`);
  }

  create(data: any): Observable<Quotation> {
    return this.http.post<Quotation>(this.url, data);
  }

  update(id: number, data: any): Observable<Quotation> {
    return this.http.patch<Quotation>(`${this.url}/${id}`, data);
  }

  changeStatus(id: number, status: string): Observable<{ status: string }> {
    return this.http.patch<{ status: string }>(`${this.url}/${id}/status`, { status });
  }

  convert(id: number): Observable<any> {
    return this.http.patch(`${this.url}/${id}/convert`, {});
  }

  delete(id: number): Observable<void> {
    return this.http.delete<void>(`${this.url}/${id}`);
  }
}

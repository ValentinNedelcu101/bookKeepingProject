import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { Navbar } from '../../../shared/components/navbar/navbar';
import { ClientService, Client } from '../../../core/services/client.service';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-client-list',
  standalone: true,
  imports: [CommonModule, RouterLink, FormsModule, Navbar],
  templateUrl: './client-list.html',
  styleUrl: './client-list.scss',
})
export class ClientList implements OnInit {
  clients: Client[] = [];
  loading = true;
  search = '';
  deleteId: number | null = null;
  deleting = false;

  constructor(
    private clientService: ClientService,
    private toast: ToastService
  ) {}

  ngOnInit() {
    this.clientService.list().subscribe({
      next: clients => {
        this.clients = clients;
        this.loading = false;
      },
      error: () => (this.loading = false),
    });
  }

  get filtered(): Client[] {
    const q = this.search.toLowerCase().trim();
    if (!q) return this.clients;
    return this.clients.filter(
      c =>
        c.name.toLowerCase().includes(q) ||
        c.contact_email?.toLowerCase().includes(q) ||
        c.phone?.toLowerCase().includes(q)
    );
  }

  confirmDelete(id: number) {
    this.deleteId = id;
  }

  cancelDelete() {
    this.deleteId = null;
  }

  doDelete() {
    if (!this.deleteId) return;
    this.deleting = true;
    const id = this.deleteId;
    this.clientService.delete(id).subscribe({
      next: () => {
        this.clients = this.clients.filter(c => c.id !== id);
        this.deleteId = null;
        this.deleting = false;
        this.toast.success('Client deleted.');
      },
      error: () => {
        this.toast.error('Could not delete client.');
        this.deleting = false;
      },
    });
  }

  initials(name: string): string {
    return name
      .split(' ')
      .slice(0, 2)
      .map(w => w[0])
      .join('')
      .toUpperCase();
  }
}

import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ToastService, Toast } from '../../../core/services/toast.service';

@Component({
  selector: 'app-toast',
  standalone: true,
  imports: [CommonModule],
  template: `
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
      @for (t of svc.toasts(); track t.id) {
        <div class="pointer-events-auto flex items-start gap-3 min-w-72 max-w-sm px-4 py-3.5 rounded-xl animate-slide-in"
             [style]="cardStyle(t)">
          <div class="shrink-0 mt-0.5">
            @if (t.type === 'success') {
              <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
            }
            @if (t.type === 'error') {
              <svg class="w-4 h-4 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            }
            @if (t.type === 'info') {
              <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            }
          </div>
          <p class="text-sm font-medium flex-1 leading-snug text-white">{{ t.message }}</p>
          <button (click)="svc.dismiss(t.id)"
            class="shrink-0 transition-colors hover:text-white mt-0.5" style="color: #929196;">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      }
    </div>
  `,
})
export class ToastComponent {
  svc = inject(ToastService);

  cardStyle(t: Toast): string {
    const base = 'background: #19181d;';
    const borders: Record<string, string> = {
      success: 'border: 1px solid rgba(34,197,94,0.25);',
      error:   'border: 1px solid rgba(239,68,68,0.25);',
      info:    'border: 1px solid rgba(99,102,241,0.25);',
    };
    return base + (borders[t.type] ?? 'border: 1px solid rgba(255,255,255,0.08);');
  }
}

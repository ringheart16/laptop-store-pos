/**
 * Laptop Store Management System
 * Core Client Utilities, Thermal Receipt Engine & Light/Dark Theme Switcher
 */

// Theme Toggle Functionality
function toggleTheme() {
  const isDark = document.documentElement.classList.toggle('dark');
  localStorage.setItem('theme', isDark ? 'dark' : 'light');
  if (window.lucide) window.lucide.createIcons();
}

// Dropdown Menus
function toggleNotifDropdown() {
  const dropdown = document.getElementById('notif-dropdown');
  const profileDropdown = document.getElementById('profile-dropdown');
  if (profileDropdown) profileDropdown.classList.add('hidden');
  if (dropdown) dropdown.classList.toggle('hidden');
}

function toggleProfileDropdown() {
  const dropdown = document.getElementById('profile-dropdown');
  const notifDropdown = document.getElementById('notif-dropdown');
  if (notifDropdown) notifDropdown.classList.add('hidden');
  if (dropdown) dropdown.classList.toggle('hidden');
}

// Click outside handler for dropdowns
document.addEventListener('click', (e) => {
  const notifContainer = document.getElementById('notif-dropdown-container');
  const profileContainer = document.getElementById('profile-dropdown-container');

  if (notifContainer && !notifContainer.contains(e.target)) {
    const d = document.getElementById('notif-dropdown');
    if (d) d.classList.add('hidden');
  }
  if (profileContainer && !profileContainer.contains(e.target)) {
    const d = document.getElementById('profile-dropdown');
    if (d) d.classList.add('hidden');
  }
});

// Toast dismiss
function dismissToast(id) {
  const toast = document.getElementById(id);
  if (toast) {
    toast.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => toast.remove(), 300);
  }
}

// Global Show Toast
function showToast(type, message) {
  const container = document.getElementById('toast-container');
  if (!container) return;

  const id = 'toast-' + Date.now();
  const borderCol = (type === 'success') ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/80 text-emerald-900 dark:text-emerald-200' :
                   ((type === 'error') ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/80 text-rose-900 dark:text-rose-200' :
                   ((type === 'warning') ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/80 text-amber-900 dark:text-amber-200' : 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/80 text-indigo-900 dark:text-indigo-200'));
  const icon = (type === 'success') ? 'check-circle' : ((type === 'error') ? 'alert-circle' : 'info');

  const div = document.createElement('div');
  div.id = id;
  div.className = `pointer-events-auto p-3.5 rounded-xl shadow-lg border-l-4 ${borderCol} flex items-start gap-2.5 transition-all duration-300 transform translate-y-2 opacity-0`;
  div.innerHTML = `
    <i data-lucide="${icon}" class="w-4 h-4 shrink-0 mt-0.5"></i>
    <div class="flex-1 text-xs font-semibold leading-relaxed">${message}</div>
    <button type="button" onclick="dismissToast('${id}')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
      <i data-lucide="x" class="w-3.5 h-3.5"></i>
    </button>
  `;

  container.appendChild(div);
  if (window.lucide) window.lucide.createIcons();

  setTimeout(() => {
    div.classList.remove('translate-y-2', 'opacity-0');
  }, 10);

  setTimeout(() => {
    dismissToast(id);
  }, 4500);
}

// Format Philippine Peso cleanly
function formatPeso(amount) {
  return '₱' + Number(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Thermal Receipt Modal Handler
function openReceiptModal(sale) {
  const modal = document.getElementById('global-receipt-modal');
  const printable = document.getElementById('printable-receipt');
  if (!modal || !printable) return;

  const subtotal = Number(sale.subtotal || sale.total_amount || 0);
  const discount = Number(sale.discount_amount || 0);
  const total = Number(sale.total_amount || 0);
  const orderNum = 'ORD-' + String(sale.sale_id || '').padStart(5, '0');
  const dateStr = sale.created_at || sale.sale_date || new Date().toISOString().split('T')[0];

  printable.innerHTML = `
    <div class="text-center pb-2.5 border-b border-dashed border-slate-300 space-y-0.5">
      <h2 class="text-sm font-bold tracking-wider text-slate-900 uppercase">LAPTOP STORE</h2>
      <p class="text-[10px] text-slate-500 uppercase tracking-wider">Official POS Receipt</p>
      <p class="text-[9px] text-slate-400">laptop_store_db | Connected Terminal</p>
    </div>

    <div class="py-2 border-b border-dashed border-slate-300 space-y-1 text-[11px]">
      <div class="flex justify-between"><span class="text-slate-500">Receipt No:</span><span class="font-bold">#${orderNum}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Date/Time:</span><span>${dateStr}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Customer:</span><span class="font-bold text-slate-800">${sale.customer_name || 'Walk-in Customer'}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Payment:</span><span class="font-semibold uppercase">${sale.payment_method || 'Cash'} (${sale.payment_status || 'Paid'})</span></div>
    </div>

    <div class="py-2 border-b border-dashed border-slate-300">
      <div class="flex justify-between font-semibold text-[10px] uppercase text-slate-400 mb-1">
        <span>Item</span>
        <span>Amount</span>
      </div>
      <div class="flex justify-between text-xs text-slate-900">
        <div>
          <p class="font-semibold">${sale.brand ? sale.brand + ' ' : ''}${sale.product_name || 'Laptop'}</p>
          <span class="text-[10px] text-slate-500">${sale.quantity || 1} unit(s)</span>
        </div>
        <p class="font-semibold">${formatPeso(subtotal)}</p>
      </div>
    </div>

    <div class="py-2 space-y-1 text-xs">
      <div class="flex justify-between text-slate-500"><span>Subtotal:</span><span>${formatPeso(subtotal)}</span></div>
      ${discount > 0 ? `<div class="flex justify-between text-rose-600"><span>Discount:</span><span>-${formatPeso(discount)}</span></div>` : ''}
      <div class="flex justify-between text-sm font-bold text-slate-900 pt-1.5 border-t border-slate-200">
        <span>TOTAL:</span>
        <span class="text-indigo-600">${formatPeso(total)}</span>
      </div>
    </div>

    <div class="pt-2 border-t border-dashed border-slate-300 text-center text-[10px] text-slate-500 space-y-0.5">
      <p class="font-semibold text-slate-700">1 Year Official Manufacturer Warranty</p>
      <p>Thank you for your purchase!</p>
    </div>
  `;

  modal.classList.remove('hidden');
  if (window.lucide) window.lucide.createIcons();
}

function closeReceiptModal() {
  const modal = document.getElementById('global-receipt-modal');
  if (modal) modal.classList.add('hidden');
}

// Mobile Sidebar Drawer
function toggleMobileSidebar(force) {
  const sidebar = document.getElementById('mobile-sidebar');
  const backdrop = document.getElementById('mobile-sidebar-backdrop');
  if (!sidebar || !backdrop) return;

  const isOpen = !sidebar.classList.contains('-translate-x-full');
  const shouldOpen = (force !== undefined) ? force : !isOpen;

  if (shouldOpen) {
    backdrop.classList.remove('hidden');
    requestAnimationFrame(() => {
      backdrop.classList.remove('opacity-0');
      sidebar.classList.remove('-translate-x-full');
    });
    document.body.classList.add('overflow-hidden');
  } else {
    backdrop.classList.add('opacity-0');
    sidebar.classList.add('-translate-x-full');
    document.body.classList.remove('overflow-hidden');
    setTimeout(() => {
      backdrop.classList.add('hidden');
    }, 300);
  }
}

// Auto-dismiss initial flash toast
document.addEventListener('DOMContentLoaded', () => {
  const initialFlash = document.getElementById('flash-toast');
  if (initialFlash) {
    setTimeout(() => {
      dismissToast('flash-toast');
    }, 4000);
  }
});

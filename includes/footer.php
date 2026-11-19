<?php
/**
 * Laptop Store Management System
 * Shared Footer with Mobile Bottom Bar & Thermal Receipt Modal
 */
$flash = getFlash();
$cur = $currentPage ?? 'dashboard';
?>

  <!-- Mobile Bottom Navigation Bar (Screens < 1024px) -->
  <nav class="lg:hidden fixed bottom-0 left-0 right-0 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md border-t border-slate-200/80 dark:border-slate-800 z-30 flex items-center justify-around px-2 select-none" style="padding-bottom: env(safe-area-inset-bottom, 0);">
    <!-- Dashboard -->
    <a href="index.php?page=dashboard" class="flex flex-col items-center justify-center flex-1 py-1.5 transition-colors <?= ($cur === 'dashboard') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
      <i data-lucide="layout-dashboard" class="w-5 h-5 mb-0.5"></i>
      <span class="text-[10px] tracking-tight">Home</span>
    </a>

    <!-- POS (Prominent Staff Shortcut) -->
    <a href="index.php?page=pos" class="flex flex-col items-center justify-center flex-1 py-1 transition-colors <?= ($cur === 'pos') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
      <div class="w-10 h-10 rounded-2xl <?= ($cur === 'pos') ? 'bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-200 dark:ring-indigo-900' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' ?> flex items-center justify-center -mt-3.5 transition-transform active:scale-95">
        <i data-lucide="store" class="w-5 h-5"></i>
      </div>
      <span class="text-[10px] tracking-tight mt-0.5">POS</span>
    </a>

    <!-- Laptops Catalog -->
    <a href="index.php?page=products" class="flex flex-col items-center justify-center flex-1 py-1.5 transition-colors <?= ($cur === 'products') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
      <i data-lucide="laptop" class="w-5 h-5 mb-0.5"></i>
      <span class="text-[10px] tracking-tight">Laptops</span>
    </a>

    <!-- Sales History -->
    <a href="index.php?page=sales" class="flex flex-col items-center justify-center flex-1 py-1.5 transition-colors <?= ($cur === 'sales') ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white' ?>">
      <i data-lucide="receipt" class="w-5 h-5 mb-0.5"></i>
      <span class="text-[10px] tracking-tight">Sales</span>
    </a>

    <!-- More Menu (Opens Drawer) -->
    <button type="button" onclick="toggleMobileSidebar(true)" class="flex flex-col items-center justify-center flex-1 py-1.5 text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors active:scale-95">
      <i data-lucide="menu" class="w-5 h-5 mb-0.5"></i>
      <span class="text-[10px] tracking-tight">Menu</span>
    </button>
  </nav>

  <!-- Toast Notification Container -->
  <div id="toast-container" class="fixed top-20 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
    <?php if ($flash): ?>
      <?php
        $type = $flash['type'] ?? 'info';
        $borderCol = ($type === 'success') ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/80 text-emerald-900 dark:text-emerald-200' :
                     (($type === 'error') ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/80 text-rose-900 dark:text-rose-200' :
                     (($type === 'warning') ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/80 text-amber-900 dark:text-amber-200' : 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950/80 text-indigo-900 dark:text-indigo-200'));
        $icon = ($type === 'success') ? 'check-circle' : (($type === 'error') ? 'alert-circle' : 'info');
      ?>
      <div class="pointer-events-auto p-3.5 rounded-xl shadow-lg border-l-4 <?= $borderCol ?> flex items-start gap-2.5 transition-all duration-300 transform translate-y-0 opacity-100" id="flash-toast">
        <i data-lucide="<?= $icon ?>" class="w-4 h-4 shrink-0 mt-0.5"></i>
        <div class="flex-1 text-xs font-semibold leading-relaxed">
          <?= htmlspecialchars($flash['message'] ?? '') ?>
        </div>
        <button type="button" onclick="dismissToast('flash-toast')" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-3.5 h-3.5"></i>
        </button>
      </div>
    <?php endif; ?>
  </div>

  <!-- Thermal POS Receipt Modal Container (Global) -->
  <div id="global-receipt-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <div class="min-h-screen px-3 sm:px-4 flex items-center justify-center">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs no-print" onclick="closeReceiptModal()"></div>
      <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl p-4 sm:p-5 max-w-sm w-full border border-slate-200 dark:border-slate-700 my-6 z-10">
        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100 dark:border-slate-700 no-print">
          <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span class="text-xs font-bold text-slate-800 dark:text-slate-100 uppercase tracking-wider">Official Thermal Receipt</span>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition-all active:scale-95">
              <i data-lucide="printer" class="w-3.5 h-3.5"></i> Print
            </button>
            <button type="button" onclick="closeReceiptModal()" class="p-1.5 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg">
              <i data-lucide="x" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <div id="printable-receipt" class="receipt-content p-3 sm:p-4 bg-slate-50 border border-dashed border-slate-300 rounded-xl font-mono text-xs text-slate-800 space-y-2.5 max-h-[70vh] overflow-y-auto">
          <!-- Injected via JS -->
        </div>
      </div>
    </div>
  </div>

  <!-- Core JavaScript Application Logic -->
  <script src="assets/js/app.js"></script>
  <script>
    if (window.lucide) {
      window.lucide.createIcons();
    }
  </script>
</body>
</html>

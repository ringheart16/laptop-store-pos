<?php
/**
 * Laptop Store Management System
 * Sidebar Navigation (Desktop & Mobile Drawer Support)
 */
$menuItems = [
    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'pos', 'label' => 'Point of Sale', 'icon' => 'store', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'products', 'label' => 'Laptop Products', 'icon' => 'laptop', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'inventory', 'label' => 'Inventory', 'icon' => 'boxes', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'sales', 'label' => 'Sales Log', 'icon' => 'receipt', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'customers', 'label' => 'Customers', 'icon' => 'users', 'allowed' => ['Admin', 'Manager', 'Cashier']],
    ['id' => 'suppliers', 'label' => 'Suppliers', 'icon' => 'truck', 'allowed' => ['Admin', 'Manager']],
    ['id' => 'reports', 'label' => 'Reports', 'icon' => 'bar-chart-3', 'allowed' => ['Admin', 'Manager']],
    ['id' => 'users', 'label' => 'Staff Users', 'icon' => 'user-check', 'allowed' => ['Admin']],
    ['id' => 'settings', 'label' => 'Settings', 'icon' => 'settings', 'allowed' => ['Admin']],
];
?>

<!-- ================= DESKTOP SIDEBAR ================= -->
<aside id="desktop-sidebar" class="hidden lg:flex fixed top-0 left-0 h-screen w-64 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 flex-col border-r border-slate-200/80 dark:border-slate-800 z-30 transition-colors duration-200">
  <!-- Brand Header -->
  <div class="h-16 flex items-center px-6 border-b border-slate-100 dark:border-slate-800">
    <a href="index.php?page=dashboard" class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-xs shrink-0">
        <i data-lucide="laptop" class="w-4 h-4"></i>
      </div>
      <div class="flex flex-col">
        <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight leading-none">LaptopStore</span>
        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-1">Management System</span>
      </div>
    </a>
  </div>

  <!-- Navigation Menu Links -->
  <div class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
    <?php foreach ($menuItems as $item): ?>
      <?php 
        if (!empty($item['allowed']) && !userHasRole($item['allowed'])) continue; 
        $isActive = ($currentPage === $item['id']);
      ?>
      <a href="index.php?page=<?= $item['id'] ?>" 
         class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs sm:text-sm font-medium transition-colors <?= $isActive 
           ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold' 
           : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/60' ?>">
        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Bottom User Profile Card -->
  <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-7 h-7 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-xs shrink-0">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="truncate">
          <p class="text-xs font-semibold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></p>
          <span class="text-[10px] text-slate-400 capitalize block leading-none mt-0.5"><?= htmlspecialchars($currentUser['role'] ?? 'Staff') ?></span>
        </div>
      </div>
      <a href="index.php?page=logout" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors" title="Sign out">
        <i data-lucide="log-out" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</aside>

<!-- ================= MOBILE OFF-CANVAS DRAWER ================= -->
<!-- Backdrop Overlay -->
<div id="mobile-sidebar-backdrop" 
     onclick="toggleMobileSidebar(false)"
     class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 hidden opacity-0 transition-opacity duration-300 lg:hidden">
</div>

<!-- Slide-over Sidebar -->
<aside id="mobile-sidebar" 
       class="fixed top-0 left-0 bottom-0 w-72 max-w-[85vw] bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 flex flex-col border-r border-slate-200/80 dark:border-slate-800 z-50 transform -translate-x-full transition-transform duration-300 ease-in-out lg:hidden shadow-2xl">
  
  <!-- Mobile Drawer Header -->
  <div class="h-16 flex items-center justify-between px-5 border-b border-slate-100 dark:border-slate-800">
    <a href="index.php?page=dashboard" onclick="toggleMobileSidebar(false)" class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-xl bg-indigo-600 flex items-center justify-center text-white shadow-xs shrink-0">
        <i data-lucide="laptop" class="w-4 h-4"></i>
      </div>
      <div class="flex flex-col">
        <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight leading-none">LaptopStore</span>
        <span class="text-[10px] text-slate-400 dark:text-slate-500 font-medium mt-0.5">Management</span>
      </div>
    </a>
    <button type="button" 
            onclick="toggleMobileSidebar(false)" 
            class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors" 
            aria-label="Close navigation">
      <i data-lucide="x" class="w-5 h-5"></i>
    </button>
  </div>

  <!-- Mobile Navigation Menu -->
  <div class="flex-1 overflow-y-auto py-3 px-3 space-y-1">
    <?php foreach ($menuItems as $item): ?>
      <?php 
        if (!empty($item['allowed']) && !userHasRole($item['allowed'])) continue; 
        $isActive = ($currentPage === $item['id']);
      ?>
      <a href="index.php?page=<?= $item['id'] ?>" 
         onclick="toggleMobileSidebar(false)"
         class="w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-colors <?= $isActive 
           ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' 
           : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-slate-800/60' ?>">
        <i data-lucide="<?= $item['icon'] ?>" class="w-4 h-4 shrink-0 <?= $isActive ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' ?>"></i>
        <span><?= htmlspecialchars($item['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Mobile Drawer Bottom User Card -->
  <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-800/40">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2.5 min-w-0">
        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-xs shrink-0">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <div class="truncate">
          <p class="text-xs font-semibold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></p>
          <span class="text-[10px] text-slate-400 capitalize block leading-none mt-0.5"><?= htmlspecialchars($currentUser['role'] ?? 'Staff') ?></span>
        </div>
      </div>
      <a href="index.php?page=logout" class="p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-xl transition-colors" title="Sign out">
        <i data-lucide="log-out" class="w-4 h-4"></i>
      </a>
    </div>
  </div>
</aside>

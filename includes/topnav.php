<?php
/**
 * Laptop Store Management System
 * Top Navigation Bar with Mobile Drawer Toggle & Light/Dark Mode
 */

$db = $pdo;
$notifCount = 0;
$notificationsList = [];
try {
    $nStmt = $db->query("SELECT product_id, product_name, brand, stock FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
    $lowItems = $nStmt->fetchAll();
    foreach ($lowItems as $item) {
        $notificationsList[] = [
            'type' => ($item['stock'] == 0) ? 'danger' : 'warning',
            'title' => ($item['stock'] == 0) ? 'Out of Stock' : 'Low Stock Warning',
            'message' => "{$item['brand']} {$item['product_name']} has {$item['stock']} units left.",
            'link' => 'index.php?page=inventory'
        ];
    }
    $notifCount = count($notificationsList);
} catch (Exception $e) {
    // Fail gracefully
}

$dbDisplayName = $GLOBALS['DB_NAME_DISPLAY'] ?? 'laptop_store_db';
?>

<header class="fixed top-0 right-0 left-0 lg:left-64 h-16 bg-white/95 dark:bg-slate-900/95 backdrop-blur-sm border-b border-slate-200/80 dark:border-slate-800 z-30 flex items-center justify-between px-3 sm:px-6 transition-colors duration-200">
  <!-- Left Side: Mobile Hamburger & Page Title -->
  <div class="flex items-center gap-2 sm:gap-3">
    <!-- Mobile Hamburger Toggle Button -->
    <button type="button" 
            onclick="toggleMobileSidebar(true)" 
            class="p-2 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors lg:hidden active:scale-95" 
            aria-label="Open Navigation Drawer">
      <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Mobile Brand Display -->
    <a href="index.php?page=dashboard" class="flex lg:hidden items-center gap-2">
      <div class="w-7 h-7 rounded-lg bg-indigo-600 flex items-center justify-center text-white shadow-xs">
        <i data-lucide="laptop" class="w-4 h-4"></i>
      </div>
      <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight truncate max-w-[120px] sm:max-w-none">LaptopStore</span>
    </a>

    <!-- Desktop Title & Status -->
    <div class="hidden lg:flex items-center gap-2.5">
      <h1 class="text-sm font-bold text-slate-900 dark:text-white capitalize">
        <?= htmlspecialchars(str_replace('_', ' ', $currentPage)) ?>
      </h1>
      <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
        <span><?= htmlspecialchars($dbDisplayName) ?></span>
      </span>
    </div>
  </div>

  <!-- Right Side: Theme Toggle, POS Action, Notifications & Profile -->
  <div class="flex items-center gap-1 sm:gap-2">
    <!-- Light / Dark Mode Toggle Button -->
    <button type="button" onclick="toggleTheme()" class="p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors active:scale-95" title="Toggle Light / Dark mode">
      <i data-lucide="sun" id="theme-icon-sun" class="w-4 h-4 hidden dark:block text-amber-400"></i>
      <i data-lucide="moon" id="theme-icon-moon" class="w-4 h-4 block dark:hidden text-slate-600"></i>
    </button>

    <?php if ($currentPage !== 'pos'): ?>
      <a href="index.php?page=pos" class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-semibold shadow-xs transition-all">
        <i data-lucide="store" class="w-3.5 h-3.5"></i>
        <span>Open POS</span>
      </a>
    <?php endif; ?>

    <!-- Low Stock Notifications Dropdown -->
    <div class="relative" id="notif-dropdown-container">
      <button type="button" onclick="toggleNotifDropdown()" class="relative p-2 text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors active:scale-95" title="Notifications">
        <i data-lucide="bell" class="w-4 h-4"></i>
        <?php if ($notifCount > 0): ?>
          <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-rose-500 ring-2 ring-white dark:ring-slate-900"></span>
        <?php endif; ?>
      </button>

      <!-- Dropdown Panel -->
      <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-72 max-w-[calc(100vw-2rem)] bg-white dark:bg-slate-800 rounded-2xl shadow-dropdown border border-slate-200 dark:border-slate-700 py-2 z-50">
        <div class="px-3.5 py-1.5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
          <span class="text-xs font-bold text-slate-800 dark:text-slate-100">Inventory Alerts</span>
          <span class="text-[10px] font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-full"><?= $notifCount ?> items</span>
        </div>
        <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700">
          <?php if (empty($notificationsList)): ?>
            <div class="p-4 text-center text-xs text-slate-400">
              <i data-lucide="check" class="w-5 h-5 text-emerald-500 mx-auto mb-1"></i>
              Stock levels healthy!
            </div>
          <?php else: ?>
            <?php foreach ($notificationsList as $notif): ?>
              <a href="<?= $notif['link'] ?>" class="block px-3.5 py-2.5 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition-colors">
                <p class="text-xs font-semibold text-slate-900 dark:text-slate-100"><?= htmlspecialchars($notif['title']) ?></p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"><?= htmlspecialchars($notif['message']) ?></p>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- User Profile Dropdown -->
    <div class="relative" id="profile-dropdown-container">
      <button type="button" onclick="toggleProfileDropdown()" class="flex items-center gap-1.5 p-1 pl-1.5 sm:pl-2 sm:pr-2.5 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition-colors active:scale-95">
        <div class="w-6 h-6 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold flex items-center justify-center text-[11px]">
          <?= strtoupper(substr($currentUser['name'] ?? 'U', 0, 1)) ?>
        </div>
        <span class="hidden sm:inline text-xs font-semibold text-slate-800 dark:text-slate-200"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></span>
        <i data-lucide="chevron-down" class="w-3 h-3 text-slate-400"></i>
      </button>

      <!-- Profile Menu -->
      <div id="profile-dropdown" class="hidden absolute right-0 mt-2 w-44 bg-white dark:bg-slate-800 rounded-2xl shadow-dropdown border border-slate-200 dark:border-slate-700 py-1.5 z-50">
        <div class="px-3 py-1.5 border-b border-slate-100 dark:border-slate-700">
          <p class="text-xs font-bold text-slate-900 dark:text-white truncate"><?= htmlspecialchars($currentUser['name'] ?? 'User') ?></p>
          <span class="text-[10px] text-slate-400 capitalize block"><?= htmlspecialchars($currentUser['role'] ?? 'Role') ?></span>
          <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-mono mt-0.5 block truncate"><?= htmlspecialchars($dbDisplayName) ?></span>
        </div>
        <?php if ($currentUser['role'] === 'Admin'): ?>
          <a href="index.php?page=settings" class="flex items-center gap-2 px-3 py-1.5 text-xs text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors">
            <i data-lucide="settings" class="w-3.5 h-3.5 text-slate-400"></i> Settings
          </a>
        <?php endif; ?>
        <a href="index.php?page=logout" class="flex items-center gap-2 px-3 py-1.5 text-xs text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
          <i data-lucide="log-out" class="w-3.5 h-3.5"></i> Sign Out
        </a>
      </div>
    </div>
  </div>
</header>

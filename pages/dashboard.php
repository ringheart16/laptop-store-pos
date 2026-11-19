<?php
/**
 * Laptop Store Management System
 * Clean, Simple & Aesthetic Store Dashboard (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager', 'Cashier']);
$db = $pdo;

// Core Metrics
$totProducts = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totStock = (int)$db->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();
$totSalesCount = (int)$db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$totRevenue = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
$lowStockCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock <= 5")->fetchColumn();

// Today's stats
$todayStmt = $db->query("SELECT COUNT(*) as trans, COALESCE(SUM(total_amount), 0) as rev FROM sales WHERE sale_date = CURDATE()");
$todayStats = $todayStmt->fetch();

// Low stock items
$lowStockItems = $db->query("SELECT * FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 4")->fetchAll();

// Recent sales
$rSales = $db->query("SELECT s.*, p.product_name, p.brand, p.price, c.name as customer_name 
                      FROM sales s 
                      JOIN products p ON s.product_id = p.product_id 
                      JOIN customers c ON s.customer_id = c.customer_id 
                      ORDER BY s.sale_id DESC LIMIT 6")->fetchAll();

// 7-day trend
$chartStmt = $db->query("SELECT sale_date, SUM(total_amount) as total 
                         FROM sales 
                         GROUP BY sale_date 
                         ORDER BY sale_date DESC LIMIT 7");
$chartRows = array_reverse($chartStmt->fetchAll());
$chartDates = array_column($chartRows, 'sale_date');
$chartTotals = array_map('floatval', array_column($chartRows, 'total'));
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">
        Store Dashboard
      </h2>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
        Overview of store revenue, hardware inventory, and today's sales
      </p>
    </div>
    <div class="flex items-center gap-2">
      <a href="index.php?page=pos" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors">
        <i data-lucide="store" class="w-4 h-4"></i>
        <span>New POS Sale</span>
      </a>
      <a href="index.php?page=products" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-medium shadow-xs transition-colors">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Add Laptop</span>
      </a>
    </div>
  </div>

  <!-- 4 KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <!-- Revenue -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Revenue</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= formatCurrency($totRevenue) ?></p>
        <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block"><?= $totSalesCount ?> total orders</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
        <i data-lucide="banknote" class="w-5 h-5"></i>
      </div>
    </div>

    <!-- Today's Sales -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Today's Sales</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= formatCurrency($todayStats['rev'] ?? 0) ?></p>
        <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-0.5 block"><?= (int)($todayStats['trans'] ?? 0) ?> orders today</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
        <i data-lucide="trending-up" class="w-5 h-5"></i>
      </div>
    </div>

    <!-- Stock -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Units in Stock</p>
        <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $totStock ?></p>
        <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5 block"><?= $totProducts ?> models</span>
      </div>
      <div class="w-10 h-10 rounded-xl bg-sky-50 dark:bg-sky-950/60 text-sky-600 dark:text-sky-400 flex items-center justify-center shrink-0">
        <i data-lucide="laptop" class="w-5 h-5"></i>
      </div>
    </div>

    <!-- Low Stock -->
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs flex items-center justify-between">
      <div>
        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Low Stock Alert</p>
        <p class="text-2xl font-bold <?= $lowStockCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-900 dark:text-white' ?> mt-1"><?= $lowStockCount ?></p>
        <span class="text-[11px] <?= $lowStockCount > 0 ? 'text-amber-600 dark:text-amber-400 font-medium' : 'text-slate-400 dark:text-slate-500' ?> mt-0.5 block">
          <?= $lowStockCount > 0 ? 'Restock needed' : 'Healthy inventory' ?>
        </span>
      </div>
      <div class="w-10 h-10 rounded-xl <?= $lowStockCount > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400' : 'bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400' ?> flex items-center justify-center shrink-0">
        <i data-lucide="alert-triangle" class="w-5 h-5"></i>
      </div>
    </div>
  </div>

  <!-- Chart & Low Stock Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <!-- Sales Trend (8 cols) -->
    <div class="lg:col-span-8 bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <div class="flex items-center justify-between mb-4">
        <div>
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Revenue Trend</h3>
          <p class="text-xs text-slate-500 dark:text-slate-400">Recent store sales performance</p>
        </div>
        <a href="index.php?page=reports" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">View Reports</a>
      </div>
      <div class="h-64">
        <canvas id="salesTrendChart"></canvas>
      </div>
    </div>

    <!-- Low Stock Notice (4 cols) -->
    <div class="lg:col-span-4 bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between mb-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white">Stock Warnings</h3>
          <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-medium"><?= count($lowStockItems) ?> flagged</span>
        </div>
        <div class="space-y-2">
          <?php if (empty($lowStockItems)): ?>
            <div class="py-8 text-center text-xs text-slate-400">
              <i data-lucide="check-circle" class="w-6 h-6 text-emerald-500 mx-auto mb-1.5"></i>
              All laptop stock levels healthy.
            </div>
          <?php else: ?>
            <?php foreach ($lowStockItems as $l): ?>
              <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-700/40 flex items-center justify-between">
                <div class="truncate mr-2">
                  <p class="text-xs font-semibold text-slate-900 dark:text-slate-100 truncate"><?= htmlspecialchars($l['product_name']) ?></p>
                  <p class="text-[10px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($l['brand']) ?> &bull; <?= formatCurrency($l['price']) ?></p>
                </div>
                <?= getStockBadge($l['stock']) ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="pt-3 mt-3 border-t border-slate-100 dark:border-slate-700">
        <a href="index.php?page=inventory" class="w-full py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold flex items-center justify-center gap-1.5 transition-colors">
          <i data-lucide="boxes" class="w-3.5 h-3.5"></i>
          <span>Open Inventory</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Recent Transactions Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="p-5 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
      <div>
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Recent Sales & POS Transactions</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Latest completed customer purchases</p>
      </div>
      <a href="index.php?page=sales" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
        View All Sales &rarr;
      </a>
    </div>

    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">Order ID</th>
            <th class="px-5 py-3">Customer</th>
            <th class="px-5 py-3">Laptop Model</th>
            <th class="px-5 py-3">Qty</th>
            <th class="px-5 py-3">Total Amount</th>
            <th class="px-5 py-3">Payment</th>
            <th class="px-5 py-3 text-center">Receipt</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($rSales)): ?>
            <tr>
              <td colspan="7" class="text-center py-8 text-slate-400">No sales transactions logged yet.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rSales as $sale): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3 font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                  #ORD-<?= str_pad($sale['sale_id'], 5, '0', STR_PAD_LEFT) ?>
                </td>
                <td class="px-5 py-3 font-medium text-slate-900 dark:text-slate-100">
                  <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?>
                </td>
                <td class="px-5 py-3">
                  <p class="font-medium text-slate-900 dark:text-slate-100"><?= htmlspecialchars($sale['product_name']) ?></p>
                  <span class="text-[10px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($sale['brand']) ?></span>
                </td>
                <td class="px-5 py-3 font-medium text-slate-600 dark:text-slate-300"><?= (int)$sale['quantity'] ?></td>
                <td class="px-5 py-3 font-semibold text-slate-900 dark:text-white"><?= formatCurrency($sale['total_amount']) ?></td>
                <td class="px-5 py-3"><?= getPaymentBadge($sale['payment_method'], $sale['payment_status'] ?? 'Paid') ?></td>
                <td class="px-5 py-3 text-center">
                  <button type="button" onclick='openReceiptModal(<?= json_encode($sale) ?>)' class="inline-flex items-center gap-1 px-2.5 py-1 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg font-semibold transition-colors">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                    <span>Receipt</span>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('salesTrendChart');
    if (ctx) {
      const dates = <?= json_encode(!empty($chartDates) ? $chartDates : [date('Y-m-d')]) ?>;
      const totals = <?= json_encode(!empty($chartTotals) ? $chartTotals : [0]) ?>;
      const isDark = document.documentElement.classList.contains('dark');

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: dates,
          datasets: [{
            label: 'Sales (₱)',
            data: totals,
            borderColor: '#6366f1',
            backgroundColor: isDark ? 'rgba(99, 102, 241, 0.15)' : 'rgba(99, 102, 241, 0.08)',
            fill: true,
            tension: 0.35,
            borderWidth: 2,
            pointBackgroundColor: '#6366f1',
            pointRadius: 4,
            pointHoverRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: isDark ? '#334155' : '#f1f5f9' },
              ticks: {
                color: isDark ? '#94a3b8' : '#64748b',
                font: { size: 11, family: 'Inter' },
                callback: function(val) { return '₱' + Number(val).toLocaleString(); }
              }
            },
            x: {
              grid: { display: false },
              ticks: {
                color: isDark ? '#94a3b8' : '#64748b',
                font: { size: 11, family: 'Inter' }
              }
            }
          }
        }
      });
    }
  });
</script>

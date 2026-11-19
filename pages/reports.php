<?php
/**
 * Laptop Store Management System
 * Reports & Analytics (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager']);
$db = $pdo;

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laptop_store_sales_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Sale Date', 'Customer Name', 'Product Name', 'Brand', 'Quantity', 'Total Amount (PHP)', 'Payment Method', 'Payment Status', 'Order Status', 'Serial Number']);

    $stmt = $db->query("SELECT s.sale_id, s.sale_date, c.name as customer_name, p.product_name, p.brand, s.quantity, s.total_amount, s.payment_method, s.payment_status, s.order_status, s.serial_number 
                        FROM sales s 
                        LEFT JOIN products p ON s.product_id = p.product_id 
                        LEFT JOIN customers c ON s.customer_id = c.customer_id 
                        ORDER BY s.sale_id DESC");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Financial calculations
$totRevenue = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales")->fetchColumn();
$totOrders = (int)$db->query("SELECT COUNT(*) FROM sales")->fetchColumn();
$avgOrder = ($totOrders > 0) ? ($totRevenue / $totOrders) : 0;

$monthRevenue = (float)$db->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())")->fetchColumn();

// Top Selling Laptops
$topProducts = $db->query("SELECT p.product_name, p.brand, SUM(s.quantity) as total_qty, SUM(s.total_amount) as total_rev 
                           FROM sales s 
                           JOIN products p ON s.product_id = p.product_id 
                           GROUP BY s.product_id 
                           ORDER BY total_qty DESC LIMIT 5")->fetchAll();

// Brand Distribution
$brandStats = $db->query("SELECT p.brand, SUM(s.quantity) as units_sold, SUM(s.total_amount) as brand_rev 
                          FROM sales s 
                          JOIN products p ON s.product_id = p.product_id 
                          GROUP BY p.brand 
                          ORDER BY brand_rev DESC LIMIT 6")->fetchAll();

$brandNames = array_column($brandStats, 'brand');
$brandRevs = array_map('floatval', array_column($brandStats, 'brand_rev'));
?>

<div class="space-y-6">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Business Reports & Analytics</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Store financial performance, revenue breakdown, and export tools</p>
    </div>
    <div class="flex items-center gap-2">
      <a href="index.php?page=reports&export=csv" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold shadow-xs transition-colors">
        <i data-lucide="download" class="w-4 h-4"></i>
        <span>Export CSV</span>
      </a>
      <button type="button" onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs transition-colors">
        <i data-lucide="printer" class="w-4 h-4"></i>
        <span>Print Summary</span>
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Gross Lifetime Revenue</p>
      <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= formatCurrency($totRevenue) ?></p>
      <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 block">All completed sales</span>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Month-to-Date Sales</p>
      <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1"><?= formatCurrency($monthRevenue) ?></p>
      <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium mt-1 block"><?= date('F Y') ?></span>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Transactions</p>
      <p class="text-2xl font-bold text-slate-900 dark:text-white mt-1"><?= $totOrders ?></p>
      <span class="text-[11px] text-slate-400 dark:text-slate-500 mt-1 block">Logged orders</span>
    </div>
    <div class="bg-white dark:bg-slate-800 p-5 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Average Order Value</p>
      <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"><?= formatCurrency($avgOrder) ?></p>
      <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium mt-1 block">Per transaction</span>
    </div>
  </div>

  <!-- Charts & Tables Grid -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
    <!-- Brand Revenue Bar (6 Cols) -->
    <div class="lg:col-span-6 bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <div class="mb-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Revenue by Laptop Brand</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Contribution of each hardware manufacturer</p>
      </div>
      <div class="h-64">
        <canvas id="brandChart"></canvas>
      </div>
    </div>

    <!-- Top Selling Laptops (6 Cols) -->
    <div class="lg:col-span-6 bg-white dark:bg-slate-800 p-5 sm:p-6 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <div class="mb-4">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Top-Selling Laptop Models</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400">Highest volume hardware units sold</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
          <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
            <tr>
              <th class="px-3 py-2.5">Model</th>
              <th class="px-3 py-2.5">Brand</th>
              <th class="px-3 py-2.5 text-center">Units</th>
              <th class="px-3 py-2.5 text-right">Revenue</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
            <?php foreach ($topProducts as $tp): ?>
              <tr>
                <td class="px-3 py-3 font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($tp['product_name']) ?></td>
                <td class="px-3 py-3"><?= getBrandBadge($tp['brand'], 'sm') ?></td>
                <td class="px-3 py-3 text-center font-bold text-slate-800 dark:text-slate-200"><?= (int)$tp['total_qty'] ?></td>
                <td class="px-3 py-3 text-right font-bold text-indigo-600 dark:text-indigo-400"><?= formatCurrency($tp['total_rev']) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('brandChart');
    if (ctx) {
      const brands = <?= json_encode(!empty($brandNames) ? $brandNames : ['Acer', 'ASUS', 'Dell', 'Lenovo']) ?>;
      const data = <?= json_encode(!empty($brandRevs) ? $brandRevs : [1000, 2000, 1500, 3000]) ?>;
      const isDark = document.documentElement.classList.contains('dark');

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: brands,
          datasets: [{
            label: 'Revenue (₱)',
            data: data,
            backgroundColor: '#6366f1',
            borderRadius: 8,
            maxBarThickness: 36
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

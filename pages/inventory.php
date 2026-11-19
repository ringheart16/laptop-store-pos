<?php
/**
 * Laptop Store Management System
 * Inventory Stock Tracking & Restocking (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager', 'Cashier']);
$db = $pdo;
$isAdminOrManager = userHasRole(['Admin', 'Manager']);

// Handle Stock Adjustment / Restock
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'adjust_stock') {
        $pId = (int)($_POST['product_id'] ?? 0);
        $addQty = (int)($_POST['quantity'] ?? 0);
        $type = $_POST['type'] ?? 'add';

        if ($pId > 0) {
            if ($type === 'add') {
                $stmt = $db->prepare("UPDATE products SET stock = stock + ? WHERE product_id = ?");
                $stmt->execute([$addQty, $pId]);
            } else {
                $stmt = $db->prepare("UPDATE products SET stock = ? WHERE product_id = ?");
                $stmt->execute([max(0, $addQty), $pId]);
            }

            // Sync inventory record
            $curStock = $db->prepare("SELECT stock FROM products WHERE product_id = ?");
            $curStock->execute([$pId]);
            $updatedStock = (int)$curStock->fetchColumn();

            $invCheck = $db->prepare("SELECT inventory_id FROM inventory WHERE product_id = ?");
            $invCheck->execute([$pId]);
            if ($invCheck->fetch()) {
                $db->prepare("UPDATE inventory SET stock = ?, last_updated = CURDATE() WHERE product_id = ?")
                   ->execute([$updatedStock, $pId]);
            } else {
                $db->prepare("INSERT INTO inventory (product_id, stock, last_updated) VALUES (?, ?, CURDATE())")
                   ->execute([$pId, $updatedStock]);
            }

            setFlash('success', "Stock level updated for product #{$pId}. Current stock: {$updatedStock} units.");
            header('Location: index.php?page=inventory');
            exit;
        }
    }
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');

$sql = "SELECT p.product_id, p.product_name, p.brand, p.price, p.stock, 
               COALESCE(i.inventory_id, 0) as inventory_id,
               COALESCE(i.last_updated, CURDATE()) as last_updated
        FROM products p
        LEFT JOIN inventory i ON p.product_id = i.product_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (p.product_name LIKE ? OR p.brand LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filter === 'low') {
    $sql .= " AND p.stock <= 5 AND p.stock > 0";
} elseif ($filter === 'out') {
    $sql .= " AND p.stock = 0";
}

$sql .= " ORDER BY p.stock ASC, p.product_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$inventoryList = $stmt->fetchAll();

// Counts for KPI pills
$totItems = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totUnits = (int)$db->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();
$lowCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock <= 5 AND stock > 0")->fetchColumn();
$outCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE stock = 0")->fetchColumn();
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Inventory Tracking</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Monitor stock levels, reorder thresholds, and log restock shipments</p>
    </div>
  </div>

  <!-- Metric Pills -->
  <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Models</p>
      <p class="text-xl font-bold text-slate-900 dark:text-white mt-0.5"><?= $totItems ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Total Units in Stock</p>
      <p class="text-xl font-bold text-indigo-600 dark:text-indigo-400 mt-0.5"><?= $totUnits ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-[11px] font-medium text-amber-600 dark:text-amber-400">Low Stock Warning</p>
      <p class="text-xl font-bold text-amber-600 dark:text-amber-400 mt-0.5"><?= $lowCount ?></p>
    </div>
    <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
      <p class="text-[11px] font-medium text-rose-600 dark:text-rose-400">Out of Stock</p>
      <p class="text-xl font-bold text-rose-600 dark:text-rose-400 mt-0.5"><?= $outCount ?></p>
    </div>
  </div>

  <!-- Filter Bar -->
  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
    <form method="GET" action="index.php" class="flex flex-col sm:flex-row gap-3 items-center justify-between">
      <input type="hidden" name="page" value="inventory">

      <div class="relative flex-1 w-full">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search laptop inventory..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
      </div>

      <div class="flex items-center gap-2 w-full sm:w-auto">
        <a href="index.php?page=inventory" class="px-3 py-2 rounded-xl text-xs font-semibold <?= empty($filter) ? 'bg-indigo-600 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200' ?>">
          All Stock
        </a>
        <a href="index.php?page=inventory&filter=low" class="px-3 py-2 rounded-xl text-xs font-semibold <?= ($filter === 'low') ? 'bg-amber-600 text-white' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 hover:bg-amber-100 border border-amber-200 dark:border-amber-800' ?>">
          Low (<?= $lowCount ?>)
        </a>
        <a href="index.php?page=inventory&filter=out" class="px-3 py-2 rounded-xl text-xs font-semibold <?= ($filter === 'out') ? 'bg-rose-600 text-white' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 hover:bg-rose-100 border border-rose-200 dark:border-rose-800' ?>">
          Out of Stock (<?= $outCount ?>)
        </a>
      </div>
    </form>
  </div>

  <!-- Inventory Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">Product ID</th>
            <th class="px-5 py-3">Laptop Model</th>
            <th class="px-5 py-3">Brand</th>
            <th class="px-5 py-3">Unit Price</th>
            <th class="px-5 py-3">Units in Stock</th>
            <th class="px-5 py-3">Stock Status</th>
            <th class="px-5 py-3">Last Updated</th>
            <?php if ($isAdminOrManager): ?>
              <th class="px-5 py-3 text-right">Action</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($inventoryList)): ?>
            <tr>
              <td colspan="8" class="text-center py-10 text-slate-400">No inventory records found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($inventoryList as $row): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3.5 font-mono text-slate-400">#<?= $row['product_id'] ?></td>
                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">
                  <?= htmlspecialchars($row['product_name']) ?>
                </td>
                <td class="px-5 py-3.5">
                  <?= getBrandBadge($row['brand'], 'sm') ?>
                </td>
                <td class="px-5 py-3.5 font-medium text-slate-800 dark:text-slate-200">
                  <?= formatCurrency($row['price']) ?>
                </td>
                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                  <?= (int)$row['stock'] ?> units
                </td>
                <td class="px-5 py-3.5">
                  <?= getStockBadge($row['stock']) ?>
                </td>
                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 text-[11px]">
                  <?= htmlspecialchars($row['last_updated'] ?? date('Y-m-d')) ?>
                </td>
                <?php if ($isAdminOrManager): ?>
                  <td class="px-5 py-3.5 text-right whitespace-nowrap">
                    <button type="button" onclick='openRestockModal(<?= json_encode($row) ?>)' class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-50 dark:bg-indigo-950/60 hover:bg-indigo-100 text-indigo-700 dark:text-indigo-400 rounded-xl text-xs font-semibold transition-colors">
                      <i data-lucide="plus-circle" class="w-3.5 h-3.5"></i>
                      <span>Restock</span>
                    </button>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================= RESTOCK MODAL ================= -->
<div id="restock-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeRestockModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Restock Laptop Units</h3>
        <button type="button" onclick="closeRestockModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=inventory" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="adjust_stock">
        <input type="hidden" name="product_id" id="restock-product-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Laptop Model</label>
          <input type="text" id="restock-product-name" readonly class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-medium cursor-not-allowed">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Current Stock</label>
            <input type="text" id="restock-current-stock" readonly class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-700 dark:text-slate-300 text-xs font-bold cursor-not-allowed">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Mode</label>
            <select name="type" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
              <option value="add">+ Add to Stock</option>
              <option value="set">= Set Exact Count</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Units Quantity *</label>
          <input type="number" name="quantity" required min="1" placeholder="e.g. 10" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeRestockModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Save Units</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openRestockModal(row) {
    document.getElementById('restock-product-id').value = row.product_id;
    document.getElementById('restock-product-name').value = row.brand + ' ' + row.product_name;
    document.getElementById('restock-current-stock').value = row.stock + ' units';

    document.getElementById('restock-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeRestockModal() {
    document.getElementById('restock-modal').classList.add('hidden');
  }
</script>

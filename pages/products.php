<?php
/**
 * Laptop Store Management System
 * Product Catalog & Laptop Management (Light & Dark Mode)
 */
checkAuthRequired();
$db = $pdo;
$isAdminOrManager = userHasRole(['Admin', 'Manager']);

// Handle Product Operations
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $pName = trim($_POST['product_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? 'Laptop');
        $specs = trim($_POST['specifications'] ?? '');

        if (!empty($pName) && !empty($brand) && $price > 0) {
            $stmt = $db->prepare("INSERT INTO products (product_name, brand, price, stock, category, specifications) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pName, $brand, $price, $stock, $category, $specs]);
            $newId = $db->lastInsertId();

            // Link to inventory
            $invStmt = $db->prepare("INSERT INTO inventory (product_id, stock, last_updated) VALUES (?, ?, CURDATE())");
            $invStmt->execute([$newId, $stock]);

            setFlash('success', "Laptop '{$pName}' added successfully!");
            header('Location: index.php?page=products');
            exit;
        } else {
            setFlash('error', "Please provide a valid product name, brand, and price.");
        }
    } elseif ($_POST['action'] === 'update') {
        $pId = (int)($_POST['product_id'] ?? 0);
        $pName = trim($_POST['product_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? 'Laptop');
        $specs = trim($_POST['specifications'] ?? '');

        if ($pId > 0 && !empty($pName) && !empty($brand)) {
            $stmt = $db->prepare("UPDATE products SET product_name = ?, brand = ?, price = ?, stock = ?, category = ?, specifications = ? WHERE product_id = ?");
            $stmt->execute([$pName, $brand, $price, $stock, $category, $specs, $pId]);

            // Sync inventory
            $invStmt = $db->prepare("UPDATE inventory SET stock = ?, last_updated = CURDATE() WHERE product_id = ?");
            $invStmt->execute([$stock, $pId]);

            setFlash('success', "Product #{$pId} updated successfully!");
            header('Location: index.php?page=products');
            exit;
        }
    } elseif ($_POST['action'] === 'delete' && userHasRole(['Admin'])) {
        $pId = (int)($_POST['product_id'] ?? 0);
        if ($pId > 0) {
            $stmt = $db->prepare("DELETE FROM products WHERE product_id = ?");
            $stmt->execute([$pId]);
            setFlash('success', "Product removed from catalog.");
            header('Location: index.php?page=products');
            exit;
        }
    }
}

// Search & Filtering
$search = trim($_GET['search'] ?? '');
$filterBrand = trim($_GET['brand'] ?? '');
$filterStock = trim($_GET['stock_filter'] ?? '');

$sql = "SELECT * FROM products WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (product_name LIKE ? OR brand LIKE ? OR specifications LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (!empty($filterBrand)) {
    $sql .= " AND brand = ?";
    $params[] = $filterBrand;
}
if ($filterStock === 'low') {
    $sql .= " AND stock <= 5 AND stock > 0";
} elseif ($filterStock === 'out') {
    $sql .= " AND stock = 0";
} elseif ($filterStock === 'in') {
    $sql .= " AND stock > 0";
}

$sql .= " ORDER BY product_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$productsList = $stmt->fetchAll();

$allBrands = $db->query("SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL AND brand != '' ORDER BY brand ASC")->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Laptop Catalog</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Manage hardware models, pricing, and hardware configurations</p>
    </div>

    <?php if ($isAdminOrManager): ?>
      <button type="button" onclick="openAddProductModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors self-start sm:self-auto">
        <i data-lucide="plus" class="w-4 h-4"></i>
        <span>Add New Laptop</span>
      </button>
    <?php endif; ?>
  </div>

  <!-- Search & Filter Bar -->
  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
    <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <input type="hidden" name="page" value="products">

      <div class="relative">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search model or specs..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
      </div>

      <div>
        <select name="brand" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500 transition-all">
          <option value="">All Brands</option>
          <?php foreach ($allBrands as $b): ?>
            <option value="<?= htmlspecialchars($b) ?>" <?= ($filterBrand === $b) ? 'selected' : '' ?>><?= htmlspecialchars($b) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <select name="stock_filter" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500 transition-all">
          <option value="">All Stock Levels</option>
          <option value="in" <?= ($filterStock === 'in') ? 'selected' : '' ?>>In Stock</option>
          <option value="low" <?= ($filterStock === 'low') ? 'selected' : '' ?>>Low Stock (&le; 5)</option>
          <option value="out" <?= ($filterStock === 'out') ? 'selected' : '' ?>>Out of Stock</option>
        </select>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white rounded-xl text-xs font-semibold transition-colors">
          Filter
        </button>
        <?php if (!empty($search) || !empty($filterBrand) || !empty($filterStock)): ?>
          <a href="index.php?page=products" class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors flex items-center justify-center" title="Reset Filters">
            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Products Table Container -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">ID</th>
            <th class="px-5 py-3">Laptop Model</th>
            <th class="px-5 py-3">Brand</th>
            <th class="px-5 py-3">Price</th>
            <th class="px-5 py-3">Stock Status</th>
            <th class="px-5 py-3">Specifications</th>
            <?php if ($isAdminOrManager): ?>
              <th class="px-5 py-3 text-right">Actions</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($productsList)): ?>
            <tr>
              <td colspan="7" class="text-center py-10 text-slate-400">No laptops matched your criteria.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($productsList as $p): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3.5 font-mono text-slate-400">#<?= $p['product_id'] ?></td>
                <td class="px-5 py-3.5 font-semibold text-slate-900 dark:text-white">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                      <i data-lucide="laptop" class="w-4 h-4"></i>
                    </div>
                    <div>
                      <p class="font-bold text-slate-900 dark:text-white"><?= htmlspecialchars($p['product_name']) ?></p>
                      <span class="text-[11px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($p['category']) ?></span>
                    </div>
                  </div>
                </td>
                <td class="px-5 py-3.5">
                  <?= getBrandBadge($p['brand'], 'sm') ?>
                </td>
                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                  <?= formatCurrency($p['price']) ?>
                </td>
                <td class="px-5 py-3.5">
                  <?= getStockBadge($p['stock']) ?>
                </td>
                <td class="px-5 py-3.5 max-w-xs">
                  <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate" title="<?= htmlspecialchars($p['specifications'] ?? '') ?>">
                    <?= htmlspecialchars($p['specifications'] ?: 'Standard configuration') ?>
                  </p>
                </td>
                <?php if ($isAdminOrManager): ?>
                  <td class="px-5 py-3.5 text-right whitespace-nowrap">
                    <button type="button" onclick='openEditProductModal(<?= json_encode($p) ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors" title="Edit">
                      <i data-lucide="edit-2" class="w-4 h-4"></i>
                    </button>
                    <?php if (userHasRole(['Admin'])): ?>
                      <form method="POST" action="index.php?page=products" onsubmit="return confirm('Delete <?= htmlspecialchars($p['product_name']) ?> from catalog?');" class="inline">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="product_id" value="<?= $p['product_id'] ?>">
                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Delete">
                          <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                      </form>
                    <?php endif; ?>
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

<!-- ================= ADD LAPTOP MODAL ================= -->
<div id="add-product-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeAddProductModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add New Laptop Model</h3>
        <button type="button" onclick="closeAddProductModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=products" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="create">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Laptop Model Name *</label>
          <input type="text" name="product_name" required placeholder="e.g. ThinkPad X1 Carbon Gen 11" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Brand *</label>
            <input type="text" name="brand" required placeholder="e.g. Lenovo" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
            <input type="text" name="category" value="Laptop" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Price (₱) *</label>
            <input type="number" step="0.01" name="price" required placeholder="49999.00" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Initial Stock Units *</label>
            <input type="number" name="stock" required placeholder="10" min="0" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hardware Specifications</label>
          <textarea name="specifications" rows="3" placeholder="Intel Core i7-1365U, 16GB LPDDR5, 512GB NVMe SSD, 14-inch IPS 2.8K..." class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500"></textarea>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeAddProductModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Save Laptop</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= EDIT LAPTOP MODAL ================= -->
<div id="edit-product-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeEditProductModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-md w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Laptop Details</h3>
        <button type="button" onclick="closeEditProductModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=products" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="product_id" id="edit-product-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Laptop Model Name *</label>
          <input type="text" name="product_name" id="edit-product-name" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Brand *</label>
            <input type="text" name="brand" id="edit-brand" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Category</label>
            <input type="text" name="category" id="edit-category" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Price (₱) *</label>
            <input type="number" step="0.01" name="price" id="edit-price" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Stock Units *</label>
            <input type="number" name="stock" id="edit-stock" required min="0" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
          </div>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Hardware Specifications</label>
          <textarea name="specifications" id="edit-specifications" rows="3" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500"></textarea>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeEditProductModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Update Laptop</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openAddProductModal() {
    document.getElementById('add-product-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeAddProductModal() {
    document.getElementById('add-product-modal').classList.add('hidden');
  }

  function openEditProductModal(product) {
    document.getElementById('edit-product-id').value = product.product_id;
    document.getElementById('edit-product-name').value = product.product_name;
    document.getElementById('edit-brand').value = product.brand;
    document.getElementById('edit-category').value = product.category || 'Laptop';
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-stock').value = product.stock;
    document.getElementById('edit-specifications').value = product.specifications || '';

    document.getElementById('edit-product-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeEditProductModal() {
    document.getElementById('edit-product-modal').classList.add('hidden');
  }
</script>

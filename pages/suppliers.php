<?php
/**
 * Laptop Store Management System
 * Supplier Management (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager']);
$db = $pdo;
$isAdmin = userHasRole(['Admin']);

// Handle Supplier Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['supplier_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (!empty($name)) {
            $stmt = $db->prepare("INSERT INTO suppliers (supplier_name, phone) VALUES (?, ?)");
            $stmt->execute([$name, $phone]);
            setFlash('success', "Supplier '{$name}' added successfully!");
            header('Location: index.php?page=suppliers');
            exit;
        } else {
            setFlash('error', "Supplier company name is required.");
        }
    } elseif ($_POST['action'] === 'update') {
        $sId = (int)($_POST['supplier_id'] ?? 0);
        $name = trim($_POST['supplier_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if ($sId > 0 && !empty($name)) {
            $stmt = $db->prepare("UPDATE suppliers SET supplier_name = ?, phone = ? WHERE supplier_id = ?");
            $stmt->execute([$name, $phone, $sId]);
            setFlash('success', "Supplier #{$sId} updated successfully!");
            header('Location: index.php?page=suppliers');
            exit;
        }
    } elseif ($_POST['action'] === 'delete' && $isAdmin) {
        $sId = (int)($_POST['supplier_id'] ?? 0);
        if ($sId > 0) {
            $stmt = $db->prepare("DELETE FROM suppliers WHERE supplier_id = ?");
            $stmt->execute([$sId]);
            setFlash('success', "Supplier removed.");
            header('Location: index.php?page=suppliers');
            exit;
        }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT * FROM suppliers WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (supplier_name LIKE ? OR phone LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " ORDER BY supplier_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$suppliersList = $stmt->fetchAll();
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Suppliers Directory</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Authorized distributors and laptop hardware vendors</p>
    </div>
    <button type="button" onclick="openAddSupplierModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors self-start sm:self-auto">
      <i data-lucide="plus" class="w-4 h-4"></i>
      <span>Add Supplier</span>
    </button>
  </div>

  <!-- Search Bar -->
  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
    <form method="GET" action="index.php" class="flex gap-2">
      <input type="hidden" name="page" value="suppliers">
      <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search supplier company or phone..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
      </div>
      <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white rounded-xl text-xs font-semibold transition-colors">
        Search
      </button>
      <?php if (!empty($search)): ?>
        <a href="index.php?page=suppliers" class="p-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors flex items-center justify-center">
          <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Suppliers Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">Supplier ID</th>
            <th class="px-5 py-3">Vendor / Supplier Name</th>
            <th class="px-5 py-3">Contact Phone</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($suppliersList)): ?>
            <tr>
              <td colspan="4" class="text-center py-10 text-slate-400">No supplier records found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($suppliersList as $s): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3.5 font-mono text-slate-400">#<?= $s['supplier_id'] ?></td>
                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                      <i data-lucide="truck" class="w-3.5 h-3.5"></i>
                    </div>
                    <span><?= htmlspecialchars($s['supplier_name']) ?></span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                  <?= htmlspecialchars($s['phone'] ?: 'N/A') ?>
                </td>
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <button type="button" onclick='openEditSupplierModal(<?= json_encode($s) ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors" title="Edit Supplier">
                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                  </button>
                  <?php if ($isAdmin): ?>
                    <form method="POST" action="index.php?page=suppliers" onsubmit="return confirm('Delete supplier <?= htmlspecialchars($s['supplier_name']) ?>?');" class="inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="supplier_id" value="<?= $s['supplier_id'] ?>">
                      <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Delete">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================= ADD SUPPLIER MODAL ================= -->
<div id="add-supplier-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeAddSupplierModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add New Supplier</h3>
        <button type="button" onclick="closeAddSupplierModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=suppliers" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="create">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Supplier Company Name *</label>
          <input type="text" name="supplier_name" required placeholder="e.g. ASUS Direct Philippines" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Contact Phone</label>
          <input type="text" name="phone" placeholder="(02) 8920-1122" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeAddSupplierModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Save Supplier</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= EDIT SUPPLIER MODAL ================= -->
<div id="edit-supplier-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeEditSupplierModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Supplier Info</h3>
        <button type="button" onclick="closeEditSupplierModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=suppliers" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="supplier_id" id="edit-supplier-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Supplier Company Name *</label>
          <input type="text" name="supplier_name" id="edit-supplier-name" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Contact Phone</label>
          <input type="text" name="phone" id="edit-supplier-phone" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeEditSupplierModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Update Supplier</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openAddSupplierModal() {
    document.getElementById('add-supplier-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeAddSupplierModal() {
    document.getElementById('add-supplier-modal').classList.add('hidden');
  }

  function openEditSupplierModal(supplier) {
    document.getElementById('edit-supplier-id').value = supplier.supplier_id;
    document.getElementById('edit-supplier-name').value = supplier.supplier_name;
    document.getElementById('edit-supplier-phone').value = supplier.phone || '';

    document.getElementById('edit-supplier-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeEditSupplierModal() {
    document.getElementById('edit-supplier-modal').classList.add('hidden');
  }
</script>

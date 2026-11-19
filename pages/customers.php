<?php
/**
 * Laptop Store Management System
 * Customer Management (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager', 'Cashier']);
$db = $pdo;

// Handle Customer Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (!empty($name)) {
            $stmt = $db->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
            $stmt->execute([$name, $phone, $email]);
            setFlash('success', "Customer '{$name}' created successfully!");
            header('Location: index.php?page=customers');
            exit;
        } else {
            setFlash('error', "Customer name is required.");
        }
    } elseif ($_POST['action'] === 'update') {
        $cId = (int)($_POST['customer_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($cId > 0 && !empty($name)) {
            $stmt = $db->prepare("UPDATE customers SET name = ?, phone = ?, email = ? WHERE customer_id = ?");
            $stmt->execute([$name, $phone, $email, $cId]);
            setFlash('success', "Customer #{$cId} updated successfully!");
            header('Location: index.php?page=customers');
            exit;
        }
    } elseif ($_POST['action'] === 'delete' && userHasRole(['Admin'])) {
        $cId = (int)($_POST['customer_id'] ?? 0);
        if ($cId > 0) {
            $stmt = $db->prepare("DELETE FROM customers WHERE customer_id = ?");
            $stmt->execute([$cId]);
            setFlash('success', "Customer record deleted.");
            header('Location: index.php?page=customers');
            exit;
        }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$sql = "SELECT c.*, 
               COUNT(s.sale_id) as total_orders, 
               COALESCE(SUM(s.total_amount), 0) as total_spent
        FROM customers c
        LEFT JOIN sales s ON c.customer_id = s.customer_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$sql .= " GROUP BY c.customer_id ORDER BY c.customer_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$customersList = $stmt->fetchAll();
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Customer Directory</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Manage client contact details and purchase order histories</p>
    </div>
    <button type="button" onclick="openAddCustomerModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors self-start sm:self-auto">
      <i data-lucide="user-plus" class="w-4 h-4"></i>
      <span>Add Customer</span>
    </button>
  </div>

  <!-- Search Bar -->
  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
    <form method="GET" action="index.php" class="flex gap-2">
      <input type="hidden" name="page" value="customers">
      <div class="relative flex-1">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer by name, phone or email..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
      </div>
      <button type="submit" class="px-4 py-2 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white rounded-xl text-xs font-semibold transition-colors">
        Search
      </button>
      <?php if (!empty($search)): ?>
        <a href="index.php?page=customers" class="p-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors flex items-center justify-center">
          <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
        </a>
      <?php endif; ?>
    </form>
  </div>

  <!-- Customers Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">ID</th>
            <th class="px-5 py-3">Customer Name</th>
            <th class="px-5 py-3">Contact Phone</th>
            <th class="px-5 py-3">Email Address</th>
            <th class="px-5 py-3">Orders</th>
            <th class="px-5 py-3">Total Spent</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($customersList)): ?>
            <tr>
              <td colspan="7" class="text-center py-10 text-slate-400">No customer records found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($customersList as $c): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3.5 font-mono text-slate-400">#<?= $c['customer_id'] ?></td>
                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 flex items-center justify-center font-bold text-[11px]">
                      <?= strtoupper(substr($c['name'] ?? 'C', 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($c['name']) ?></span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                  <?= htmlspecialchars($c['phone'] ?: 'N/A') ?>
                </td>
                <td class="px-5 py-3.5 text-slate-600 dark:text-slate-300">
                  <?= htmlspecialchars($c['email'] ?: 'N/A') ?>
                </td>
                <td class="px-5 py-3.5 font-semibold text-slate-800 dark:text-slate-200">
                  <?= (int)$c['total_orders'] ?>
                </td>
                <td class="px-5 py-3.5 font-bold text-indigo-600 dark:text-indigo-400">
                  <?= formatCurrency($c['total_spent']) ?>
                </td>
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <button type="button" onclick='openEditCustomerModal(<?= json_encode($c) ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors" title="Edit Customer">
                    <i data-lucide="edit-2" class="w-4 h-4"></i>
                  </button>
                  <?php if (userHasRole(['Admin'])): ?>
                    <form method="POST" action="index.php?page=customers" onsubmit="return confirm('Delete customer <?= htmlspecialchars($c['name']) ?>?');" class="inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="customer_id" value="<?= $c['customer_id'] ?>">
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

<!-- ================= ADD CUSTOMER MODAL ================= -->
<div id="add-customer-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeAddCustomerModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add New Customer</h3>
        <button type="button" onclick="closeAddCustomerModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=customers" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="create">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
          <input type="text" name="name" required placeholder="e.g. Maria Santos" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
          <input type="text" name="phone" placeholder="0917-123-4567" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
          <input type="email" name="email" placeholder="customer@example.com" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeAddCustomerModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Save Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= EDIT CUSTOMER MODAL ================= -->
<div id="edit-customer-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeEditCustomerModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Customer Info</h3>
        <button type="button" onclick="closeEditCustomerModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=customers" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="customer_id" id="edit-customer-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
          <input type="text" name="name" id="edit-customer-name" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
          <input type="text" name="phone" id="edit-customer-phone" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address</label>
          <input type="email" name="email" id="edit-customer-email" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeEditCustomerModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Update Customer</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openAddCustomerModal() {
    document.getElementById('add-customer-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeAddCustomerModal() {
    document.getElementById('add-customer-modal').classList.add('hidden');
  }

  function openEditCustomerModal(customer) {
    document.getElementById('edit-customer-id').value = customer.customer_id;
    document.getElementById('edit-customer-name').value = customer.name;
    document.getElementById('edit-customer-phone').value = customer.phone || '';
    document.getElementById('edit-customer-email').value = customer.email || '';

    document.getElementById('edit-customer-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeEditCustomerModal() {
    document.getElementById('edit-customer-modal').classList.add('hidden');
  }
</script>

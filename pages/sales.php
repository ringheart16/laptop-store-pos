<?php
/**
 * Laptop Store Management System
 * Sales History & Transaction Log (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager', 'Cashier']);
$db = $pdo;
$isAdminOrManager = userHasRole(['Admin', 'Manager']);

// Handle Status Update
if ($isAdminOrManager && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_status') {
        $saleId = (int)($_POST['sale_id'] ?? 0);
        $orderStatus = trim($_POST['order_status'] ?? 'Delivered');
        $paymentStatus = trim($_POST['payment_status'] ?? 'Paid');

        if ($saleId > 0) {
            $stmt = $db->prepare("UPDATE sales SET order_status = ?, payment_status = ? WHERE sale_id = ?");
            $stmt->execute([$orderStatus, $paymentStatus, $saleId]);
            setFlash('success', "Order #{$saleId} status updated to '{$orderStatus}'.");
            header('Location: index.php?page=sales');
            exit;
        }
    }
}

// Search & Filters
$search = trim($_GET['search'] ?? '');
$filterMethod = trim($_GET['payment_method'] ?? '');
$filterStatus = trim($_GET['order_status'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo = trim($_GET['date_to'] ?? '');

$sql = "SELECT s.*, 
               p.product_name, p.brand, p.price,
               c.name as customer_name, c.phone as customer_phone
        FROM sales s
        LEFT JOIN products p ON s.product_id = p.product_id
        LEFT JOIN customers c ON s.customer_id = c.customer_id
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.name LIKE ? OR p.product_name LIKE ? OR s.sale_id LIKE ? OR s.serial_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if (!empty($filterMethod)) {
    $sql .= " AND s.payment_method = ?";
    $params[] = $filterMethod;
}
if (!empty($filterStatus)) {
    $sql .= " AND s.order_status = ?";
    $params[] = $filterStatus;
}
if (!empty($dateFrom)) {
    $sql .= " AND s.sale_date >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo)) {
    $sql .= " AND s.sale_date <= ?";
    $params[] = $dateTo;
}

$sql .= " ORDER BY s.sale_id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$salesList = $stmt->fetchAll();

$totRevenue = array_sum(array_column($salesList, 'total_amount'));
$totCount = count($salesList);
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Sales & Orders Log</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Official log of store point-of-sale transactions and orders</p>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-xs font-semibold text-slate-500 dark:text-slate-400"><?= $totCount ?> transactions | Total: <span class="font-bold text-indigo-600 dark:text-indigo-400"><?= formatCurrency($totRevenue) ?></span></span>
    </div>
  </div>

  <!-- Search & Filter Bar -->
  <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs">
    <form method="GET" action="index.php" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
      <input type="hidden" name="page" value="sales">

      <div class="relative lg:col-span-2">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search customer, order ID, model..." class="w-full pl-9 pr-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:border-indigo-500 transition-all">
      </div>

      <div>
        <select name="payment_method" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500">
          <option value="">All Payment Methods</option>
          <option value="Cash" <?= ($filterMethod === 'Cash') ? 'selected' : '' ?>>Cash</option>
          <option value="GCash" <?= ($filterMethod === 'GCash') ? 'selected' : '' ?>>GCash</option>
          <option value="Maya" <?= ($filterMethod === 'Maya') ? 'selected' : '' ?>>Maya</option>
          <option value="Credit Card" <?= ($filterMethod === 'Credit Card') ? 'selected' : '' ?>>Credit Card</option>
          <option value="Bank Transfer" <?= ($filterMethod === 'Bank Transfer') ? 'selected' : '' ?>>Bank Transfer</option>
        </select>
      </div>

      <div>
        <select name="order_status" onchange="this.form.submit()" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs text-slate-800 dark:text-slate-100 focus:outline-none focus:border-indigo-500">
          <option value="">All Order Statuses</option>
          <option value="Delivered" <?= ($filterStatus === 'Delivered') ? 'selected' : '' ?>>Delivered</option>
          <option value="Pending" <?= ($filterStatus === 'Pending') ? 'selected' : '' ?>>Pending</option>
          <option value="Processing" <?= ($filterStatus === 'Processing') ? 'selected' : '' ?>>Processing</option>
          <option value="Cancelled" <?= ($filterStatus === 'Cancelled') ? 'selected' : '' ?>>Cancelled</option>
        </select>
      </div>

      <div class="flex gap-2">
        <button type="submit" class="flex-1 py-2 px-3 bg-slate-800 dark:bg-slate-700 hover:bg-slate-900 dark:hover:bg-slate-600 text-white rounded-xl text-xs font-semibold transition-colors">
          Filter
        </button>
        <?php if (!empty($search) || !empty($filterMethod) || !empty($filterStatus)): ?>
          <a href="index.php?page=sales" class="py-2 px-3 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-600 dark:text-slate-300 rounded-xl text-xs font-semibold transition-colors flex items-center justify-center">
            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
          </a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <!-- Sales Log Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">Order ID</th>
            <th class="px-5 py-3">Date</th>
            <th class="px-5 py-3">Customer</th>
            <th class="px-5 py-3">Laptop Purchased</th>
            <th class="px-5 py-3">Qty</th>
            <th class="px-5 py-3">Total Amount</th>
            <th class="px-5 py-3">Payment</th>
            <th class="px-5 py-3">Order Status</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php if (empty($salesList)): ?>
            <tr>
              <td colspan="9" class="text-center py-10 text-slate-400">No transactions match the selected filter.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($salesList as $sale): ?>
              <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
                <td class="px-5 py-3.5 font-mono font-semibold text-indigo-600 dark:text-indigo-400">
                  #ORD-<?= str_pad($sale['sale_id'], 5, '0', STR_PAD_LEFT) ?>
                </td>
                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  <?= htmlspecialchars($sale['sale_date']) ?>
                </td>
                <td class="px-5 py-3.5 font-medium text-slate-900 dark:text-white">
                  <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?>
                </td>
                <td class="px-5 py-3.5">
                  <p class="font-semibold text-slate-900 dark:text-white"><?= htmlspecialchars($sale['product_name'] ?? 'Laptop Item') ?></p>
                  <span class="text-[10px] text-slate-400 dark:text-slate-500"><?= htmlspecialchars($sale['brand'] ?? '') ?></span>
                </td>
                <td class="px-5 py-3.5 font-medium text-slate-600 dark:text-slate-300"><?= (int)$sale['quantity'] ?></td>
                <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white"><?= formatCurrency($sale['total_amount']) ?></td>
                <td class="px-5 py-3.5">
                  <?= getPaymentBadge($sale['payment_method'], $sale['payment_status'] ?? 'Paid') ?>
                </td>
                <td class="px-5 py-3.5">
                  <?= getOrderStatusBadge($sale['order_status'] ?? 'Delivered') ?>
                </td>
                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                  <button type="button" onclick='openReceiptModal(<?= json_encode($sale) ?>)' class="inline-flex items-center gap-1 px-2.5 py-1 text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/40 rounded-lg font-semibold transition-colors mr-1">
                    <i data-lucide="receipt" class="w-3.5 h-3.5"></i>
                    <span>Receipt</span>
                  </button>
                  <?php if ($isAdminOrManager): ?>
                    <button type="button" onclick='openStatusModal(<?= json_encode($sale) ?>)' class="p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors" title="Update Status">
                      <i data-lucide="more-horizontal" class="w-4 h-4"></i>
                    </button>
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

<!-- ================= UPDATE STATUS MODAL ================= -->
<div id="status-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeStatusModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Update Order Status</h3>
        <button type="button" onclick="closeStatusModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=sales" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="sale_id" id="status-sale-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Order Status</label>
          <select name="order_status" id="status-order" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
            <option value="Delivered">Delivered</option>
            <option value="Processing">Processing</option>
            <option value="Pending">Pending</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Payment Status</label>
          <select name="payment_status" id="status-payment" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
            <option value="Paid">Paid</option>
            <option value="Pending">Pending</option>
          </select>
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeStatusModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Update Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openStatusModal(sale) {
    document.getElementById('status-sale-id').value = sale.sale_id;
    document.getElementById('status-order').value = sale.order_status || 'Delivered';
    document.getElementById('status-payment').value = sale.payment_status || 'Paid';

    document.getElementById('status-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeStatusModal() {
    document.getElementById('status-modal').classList.add('hidden');
  }
</script>

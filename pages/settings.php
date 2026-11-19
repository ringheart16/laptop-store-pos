<?php
/**
 * Laptop Store Management System
 * Store Settings (Light & Dark Mode)
 */
checkAuthRequired(['Admin']);

$configFile = __DIR__ . '/../api/config/store_settings.json';
$defaultSettings = [
    'store_name' => 'Laptop Store Philippines',
    'tagline' => 'Official Authorized Laptop & Computer Retailer',
    'phone' => '(02) 8889-5278',
    'email' => 'sales@laptopstore.ph',
    'address' => 'Cyberzone SM Megamall, Mandaluyong City, Metro Manila',
    'currency' => '₱',
    'tax_rate' => '12',
    'receipt_note' => 'Please present this receipt for warranty claims & replacement within 7 days.'
];

$settings = $defaultSettings;
if (file_exists($configFile)) {
    $loaded = json_decode(file_get_contents($configFile), true);
    if (is_array($loaded)) {
        $settings = array_merge($defaultSettings, $loaded);
    }
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newSettings = [
        'store_name' => trim($_POST['store_name'] ?? $defaultSettings['store_name']),
        'tagline' => trim($_POST['tagline'] ?? $defaultSettings['tagline']),
        'phone' => trim($_POST['phone'] ?? $defaultSettings['phone']),
        'email' => trim($_POST['email'] ?? $defaultSettings['email']),
        'address' => trim($_POST['address'] ?? $defaultSettings['address']),
        'currency' => trim($_POST['currency'] ?? '₱'),
        'tax_rate' => trim($_POST['tax_rate'] ?? '12'),
        'receipt_note' => trim($_POST['receipt_note'] ?? $defaultSettings['receipt_note']),
    ];

    file_put_contents($configFile, json_encode($newSettings, JSON_PRETTY_PRINT));
    setFlash('success', 'Store settings updated successfully.');
    header('Location: index.php?page=settings');
    exit;
}
?>

<div class="max-w-3xl space-y-5">
  <div>
    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Store Settings</h2>
    <p class="text-xs text-slate-500 dark:text-slate-400">Configure receipt headers, business contact details, and POS preferences</p>
  </div>

  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs p-6">
    <form method="POST" action="index.php?page=settings" class="space-y-4 text-xs">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Business Name *</label>
          <input type="text" name="store_name" value="<?= htmlspecialchars($settings['store_name']) ?>" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Tagline</label>
          <input type="text" name="tagline" value="<?= htmlspecialchars($settings['tagline']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Contact Phone</label>
          <input type="text" name="phone" value="<?= htmlspecialchars($settings['phone']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Contact Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($settings['email']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
      </div>

      <div>
        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Store Physical Address</label>
        <input type="text" name="address" value="<?= htmlspecialchars($settings['address']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Currency Symbol</label>
          <input type="text" name="currency" value="<?= htmlspecialchars($settings['currency']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">VAT / Tax Rate (%)</label>
          <input type="number" step="0.1" name="tax_rate" value="<?= htmlspecialchars($settings['tax_rate']) ?>" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>
      </div>

      <div>
        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Receipt Footer Warranty Note</label>
        <textarea name="receipt_note" rows="2" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500"><?= htmlspecialchars($settings['receipt_note']) ?></textarea>
      </div>

      <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex justify-end">
        <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs transition-colors">
          Save Settings
        </button>
      </div>
    </form>
  </div>
</div>

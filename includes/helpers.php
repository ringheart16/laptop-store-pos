<?php
/**
 * Laptop Store Management System
 * Core Helpers & UI Badge Generators (Light/Dark Mode Support)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../api/config/database.php';

/**
 * Format currency with Philippine Peso symbol
 */
function formatCurrency($amount) {
    return '₱' . number_format((float)($amount ?? 0), 2, '.', ',');
}

/**
 * Get current authenticated user
 */
function authUser() {
    if (isset($_SESSION['user_id'])) {
        return [
            'user_id' => (int)$_SESSION['user_id'],
            'name' => $_SESSION['name'] ?? 'User',
            'username' => $_SESSION['username'] ?? '',
            'role' => ucfirst(strtolower($_SESSION['role'] ?? 'Cashier'))
        ];
    }
    return null;
}

/**
 * Check if current user has permission
 */
function userHasRole($allowedRoles) {
    $user = authUser();
    if (!$user) return false;
    $allowedLower = array_map('strtolower', (array)$allowedRoles);
    return in_array(strtolower($user['role']), $allowedLower);
}

/**
 * Require authentication or redirect
 */
function checkAuthRequired($allowedRoles = null) {
    $user = authUser();
    if (!$user) {
        header('Location: index.php?page=login');
        exit;
    }
    if ($allowedRoles && !userHasRole($allowedRoles)) {
        header('Location: index.php?page=dashboard&error=access_denied');
        exit;
    }
    return $user;
}

/**
 * Set flash toast message
 */
function setFlash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success' | 'error' | 'warning' | 'info'
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Generate bespoke laptop brand badge
 */
function getBrandBadge($brand, $size = 'md') {
    $b = strtolower(trim($brand ?? ''));
    
    $classes = [
        'apple'  => 'bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 border-slate-800 dark:border-slate-200',
        'asus'   => 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800',
        'lenovo' => 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border-rose-200 dark:border-rose-800',
        'dell'   => 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800',
        'hp'     => 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
        'acer'   => 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
        'msi'    => 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
        'razer'  => 'bg-slate-900 dark:bg-slate-950 text-emerald-400 border-slate-800 dark:border-emerald-900',
    ];

    $matchedClass = 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700';
    foreach ($classes as $key => $cls) {
        if (strpos($b, $key) !== false) {
            $matchedClass = $cls;
            break;
        }
    }

    $sizeClass = ($size === 'sm') ? 'text-[11px] px-2 py-0.5' : 'text-xs px-2.5 py-0.5 font-semibold';
    $displayBrand = htmlspecialchars($brand ?: 'Laptop');

    return "<span class=\"inline-flex items-center gap-1.5 rounded-lg border {$matchedClass} {$sizeClass}\">
        <span class=\"w-1.5 h-1.5 rounded-full bg-current opacity-70\"></span>
        <span>{$displayBrand}</span>
    </span>";
}

/**
 * Extract and output hardware specs pills (CPU, RAM, GPU, Storage)
 */
function getSpecsPills($title, $rawSpecs = '') {
    $text = ($title ?? '') . ' ' . ($rawSpecs ?? '');
    $pills = [];

    // CPU detection
    if (preg_match('/(Core\s?i[3579][^\s,]*|Ryzen\s?[3579][^\s,]*|M[1234]\s?(Pro|Max|Ultra)?|Intel\s?[^,\n]*|AMD\s?[^,\n]*)/i', $text, $m)) {
        $pills[] = ['icon' => 'CPU', 'label' => trim($m[0])];
    }
    // RAM detection
    if (preg_match('/(\d{1,2}\s?GB(\s?RAM|\s?DDR[45]|\s?Unified|\s?LPDDR[45]X?)?)/i', $text, $m)) {
        $pills[] = ['icon' => 'RAM', 'label' => trim($m[0])];
    }
    // Storage detection
    if (preg_match('/(\d{1,3}\s?GB|\d\s?TB)\s?(SSD|NVMe|PCIe)?/i', $text, $m)) {
        $pills[] = ['icon' => 'SSD', 'label' => trim($m[0])];
    }
    // GPU detection
    if (preg_match('/(RTX\s?\d{4}[^\s,]*|GTX\s?\d{4}[^\s,]*|Radeon[^\s,]*|Iris\s?Xe|\d-Core GPU)/i', $text, $m)) {
        $pills[] = ['icon' => 'GPU', 'label' => trim($m[0])];
    }

    if (empty($pills)) {
        $pills[] = ['icon' => 'Specs', 'label' => 'Standard Config'];
    }

    $html = '<div class="flex flex-wrap items-center gap-1.5 mt-2">';
    foreach (array_slice($pills, 0, 3) as $p) {
        $label = htmlspecialchars($p['label']);
        $html .= "<span class=\"inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200/80 dark:border-slate-700\">
            <span class=\"text-[10px] uppercase font-semibold text-slate-400 dark:text-slate-500\">{$p['icon']}</span>
            <span class=\"truncate max-w-[120px]\">{$label}</span>
        </span>";
    }
    $html .= '</div>';
    return $html;
}

/**
 * Stock Status Badge
 */
function getStockBadge($stock) {
    $stock = (int)$stock;
    if ($stock <= 0) {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
            Out of Stock
        </span>';
    }
    if ($stock <= 5) {
        return "<span class=\"inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800\">
            <span class=\"w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse\"></span>
            Low ({$stock} left)
        </span>";
    }
    return "<span class=\"inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800\">
        <span class=\"w-1.5 h-1.5 rounded-full bg-emerald-500\"></span>
        In Stock ({$stock})
    </span>";
}

/**
 * Order Status Badge
 */
function getOrderStatusBadge($status) {
    $s = strtolower(trim($status ?? 'delivered'));
    if ($s === 'delivered' || $s === 'completed') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            Delivered
        </span>';
    }
    if ($s === 'out for delivery' || $s === 'ready for delivery') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
            <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            ' . htmlspecialchars($status) . '
        </span>';
    }
    if ($s === 'preparing' || $s === 'confirmed' || $s === 'processing' || $s === 'pending') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
            ' . htmlspecialchars($status) . '
        </span>';
    }
    if ($s === 'cancelled' || $s === 'failed') {
        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-800">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
            ' . htmlspecialchars($status) . '
        </span>';
    }
    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700">' . htmlspecialchars($status) . '</span>';
}

/**
 * Payment Badge
 */
function getPaymentBadge($method, $status = 'Paid') {
    $m = htmlspecialchars($method ?: 'Cash');
    $st = strtolower(trim($status ?: 'paid'));
    
    $badgeClass = ($st === 'paid') 
        ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800' 
        : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800';
    return "<span class=\"inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-medium border {$badgeClass}\">
        <span class=\"font-semibold\">{$m}</span>
        <span class=\"text-[11px] opacity-75\">({$status})</span>
    </span>";
}

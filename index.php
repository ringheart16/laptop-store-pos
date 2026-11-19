<?php
/**
 * Laptop Store Management System
 * Front Controller & Router (Native PHP, Tailwind CSS, Node.js)
 */

ob_start();
header('Content-Type: text/html; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/helpers.php';

$page = $_GET['page'] ?? 'dashboard';

// Handle Logout
if ($page === 'logout') {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header('Location: index.php?page=login');
    exit;
}

// Standalone pages
if ($page === 'login') {
    require __DIR__ . '/pages/login.php';
    exit;
}

if ($page === 'register') {
    require __DIR__ . '/pages/register.php';
    exit;
}

// Require login for dashboard & management
$currentUser = checkAuthRequired();

// Valid Store Management Pages
$validPages = [
    'dashboard'  => 'pages/dashboard.php',
    'pos'        => 'pages/pos.php',
    'products'   => 'pages/products.php',
    'inventory'  => 'pages/inventory.php',
    'customers'  => 'pages/customers.php',
    'suppliers'  => 'pages/suppliers.php',
    'sales'      => 'pages/sales.php',
    'reports'    => 'pages/reports.php',
    'users'      => 'pages/users.php',
    'settings'   => 'pages/settings.php',
];

$pageFile = $validPages[$page] ?? 'pages/dashboard.php';

// Render full application layout
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/sidebar.php';
?>

<!-- Main Layout Wrapper -->
<div class="lg:ml-64 flex-1 flex flex-col min-h-screen">
  <!-- Top Navigation Bar -->
  <?php require __DIR__ . '/includes/topnav.php'; ?>

  <!-- Main Content Container -->
  <main class="flex-1 pt-20 pb-24 lg:pb-10 px-3 sm:px-6 lg:px-8 max-w-7xl w-full mx-auto">
    <?php require __DIR__ . '/' . $pageFile; ?>
  </main>
</div>

<!-- Shared Footer, Receipt Modals, & JS Engine -->
<?php require __DIR__ . '/includes/footer.php'; ?>

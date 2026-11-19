<?php
/**
 * Laptop Store Management System
 * Create an Account (Light & Dark Mode)
 */

header('Content-Type: text/html; charset=utf-8');

if (authUser()) {
    header('Location: index.php?page=dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $role = trim($_POST['role'] ?? 'Cashier');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Allowed roles
    $validRoles = ['Admin', 'Manager', 'Cashier'];
    if (!in_array($role, $validRoles)) {
        $role = 'Cashier';
    }

    if (empty($name) || empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please verify.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = "Username '{$username}' is already taken. Please choose another.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
                $insert->execute([$name, $username, $hashed, $role]);
                $newUserId = (int)$pdo->lastInsertId();

                // Auto login user
                session_regenerate_id(true);
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['name'] = $name;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;

                setFlash('success', "Account created successfully! Welcome, {$name}.");
                header('Location: index.php?page=dashboard');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Create an Account | Laptop Store Management System</title>
  <script>
    if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  </script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] }
        }
      }
    }
  </script>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 font-sans antialiased min-h-screen flex items-center justify-center p-4 relative transition-colors duration-200">
  <!-- Theme Toggle Floating Button -->
  <button type="button" onclick="toggleTheme()" class="absolute top-4 right-4 p-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 rounded-xl shadow-xs hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors" title="Toggle Light/Dark Theme">
    <i data-lucide="sun" class="w-4 h-4 hidden dark:block text-amber-400"></i>
    <i data-lucide="moon" class="w-4 h-4 block dark:hidden text-slate-600"></i>
  </button>

  <div class="max-w-md w-full">
    <!-- Brand Title -->
    <div class="text-center mb-6">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-600 text-white shadow-sm mb-3">
        <i data-lucide="laptop" class="w-6 h-6"></i>
      </div>
      <h1 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Create an Account</h1>
      <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Register new credentials to access the Laptop Store Management System</p>
    </div>

    <!-- Registration Card -->
    <div class="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-slate-700 p-6 sm:p-8">
      <!-- Tabs for Sign In / Create Account -->
      <div class="flex items-center p-1 bg-slate-100 dark:bg-slate-700/60 rounded-xl mb-5 text-xs font-semibold">
        <a href="index.php?page=login" class="flex-1 py-1.5 text-center text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white transition-colors">
          Sign In
        </a>
        <a href="index.php?page=register" class="flex-1 py-1.5 text-center bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-lg shadow-xs">
          Create Account
        </a>
      </div>

      <?php if (!empty($error)): ?>
        <div class="mb-5 p-3.5 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-400 text-xs rounded-xl flex items-center gap-2">
          <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
          <span><?= htmlspecialchars($error) ?></span>
        </div>
      <?php endif; ?>

      <form method="POST" action="index.php?page=register" class="space-y-3.5">
        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
          <div class="relative">
            <i data-lucide="user" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="text" name="name" required placeholder="e.g. Alex Rivera" class="w-full pl-9 pr-3.5 py-2.5 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Username *</label>
            <div class="relative">
              <i data-lucide="at-sign" class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
              <input type="text" name="username" required placeholder="username" class="w-full pl-8 pr-3 py-2.5 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            </div>
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
            <select name="role" class="w-full px-3 py-2.5 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-all">
              <option value="Cashier">Cashier</option>
              <option value="Manager">Manager</option>
              <option value="Admin">Admin</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Password *</label>
          <div class="relative">
            <i data-lucide="lock" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="password" name="password" id="reg-password" required placeholder="Minimum 4 characters" class="w-full pl-9 pr-10 py-2.5 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            <button type="button" onclick="toggleRegPassword('reg-password', 'reg-eye-1')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <i data-lucide="eye" id="reg-eye-1" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <div>
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Confirm Password *</label>
          <div class="relative">
            <i data-lucide="shield-check" class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
            <input type="password" name="confirm_password" id="reg-confirm-password" required placeholder="Re-type your password" class="w-full pl-9 pr-10 py-2.5 bg-white dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-xs sm:text-sm text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            <button type="button" onclick="toggleRegPassword('reg-confirm-password', 'reg-eye-2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
              <i data-lucide="eye" id="reg-eye-2" class="w-4 h-4"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="w-full mt-2 py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl text-xs sm:text-sm shadow-sm transition-all active:scale-[0.99] flex items-center justify-center gap-2">
          <span>Create Account & Sign In</span>
          <i data-lucide="arrow-right" class="w-4 h-4"></i>
        </button>
      </form>

      <div class="mt-4 text-center">
        <p class="text-xs text-slate-500 dark:text-slate-400">
          Already registered? 
          <a href="index.php?page=login" class="font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
            Sign In here &rarr;
          </a>
        </p>
      </div>
    </div>

    <div class="text-center mt-6 text-xs text-slate-400 dark:text-slate-500">
      <p>Laptop Store Management System &bull; MySQL: <span class="font-medium text-slate-600 dark:text-slate-300">laptop_store_db</span></p>
    </div>
  </div>

  <script>
    if (window.lucide) window.lucide.createIcons();

    function toggleTheme() {
      const isDark = document.documentElement.classList.toggle('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      if (window.lucide) window.lucide.createIcons();
    }

    function toggleRegPassword(inputId, eyeId) {
      const input = document.getElementById(inputId);
      const eye = document.getElementById(eyeId);
      if (input.type === 'password') {
        input.type = 'text';
        eye.setAttribute('data-lucide', 'eye-off');
      } else {
        input.type = 'password';
        eye.setAttribute('data-lucide', 'eye');
      }
      if (window.lucide) window.lucide.createIcons();
    }
  </script>
</body>
</html>

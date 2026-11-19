<?php
/**
 * Laptop Store Management System
 * Staff & User Management (Light & Dark Mode)
 */
checkAuthRequired(['Admin']);
$db = $pdo;

// Handle User Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = trim($_POST['role'] ?? 'Cashier');

        if (!empty($name) && !empty($username) && !empty($password)) {
            $chk = $db->prepare("SELECT user_id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if ($chk->fetch()) {
                setFlash('error', "Username '{$username}' is already taken.");
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO users (name, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $username, $hash, $role]);
                setFlash('success', "User '{$name}' created with role {$role}.");
                header('Location: index.php?page=users');
                exit;
            }
        } else {
            setFlash('error', "All fields are required.");
        }
    } elseif ($_POST['action'] === 'update') {
        $uId = (int)($_POST['user_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $role = trim($_POST['role'] ?? 'Cashier');
        $password = $_POST['password'] ?? '';

        if ($uId > 0 && !empty($name) && !empty($username)) {
            if (!empty($password)) {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("UPDATE users SET name = ?, username = ?, role = ?, password = ? WHERE user_id = ?");
                $stmt->execute([$name, $username, $role, $hash, $uId]);
            } else {
                $stmt = $db->prepare("UPDATE users SET name = ?, username = ?, role = ? WHERE user_id = ?");
                $stmt->execute([$name, $username, $role, $uId]);
            }
            setFlash('success', "User #{$uId} updated successfully!");
            header('Location: index.php?page=users');
            exit;
        }
    } elseif ($_POST['action'] === 'delete') {
        $uId = (int)($_POST['user_id'] ?? 0);
        if ($uId === (int)$currentUser['user_id']) {
            setFlash('error', "You cannot delete your own active account.");
        } elseif ($uId > 0) {
            $stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$uId]);
            setFlash('success', "User account deleted.");
            header('Location: index.php?page=users');
            exit;
        }
    }
}

// Fetch users
$users = $db->query("SELECT user_id, name, username, role FROM users ORDER BY user_id ASC")->fetchAll();
?>

<div class="space-y-5">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight">Staff & User Management</h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Configure administrative, manager, and cashier access credentials</p>
    </div>
    <button type="button" onclick="openAddUserModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs sm:text-sm font-semibold shadow-xs transition-colors self-start sm:self-auto">
      <i data-lucide="user-plus" class="w-4 h-4"></i>
      <span>Add New Staff</span>
    </button>
  </div>

  <!-- Users Table -->
  <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 dark:bg-slate-800/80 text-slate-500 dark:text-slate-400 font-semibold border-b border-slate-100 dark:border-slate-700">
          <tr>
            <th class="px-5 py-3">ID</th>
            <th class="px-5 py-3">Full Name</th>
            <th class="px-5 py-3">Username</th>
            <th class="px-5 py-3">Role</th>
            <th class="px-5 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
          <?php foreach ($users as $u): ?>
            <?php 
              $roleLower = strtolower($u['role'] ?? '');
              $roleBadge = ($roleLower === 'admin') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border-indigo-200 dark:border-indigo-800' :
                           (($roleLower === 'manager') ? 'bg-sky-50 dark:bg-sky-950/60 text-sky-700 dark:text-sky-400 border-sky-200 dark:border-sky-800' : 'bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-600');
            ?>
            <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-700/40 transition-colors">
              <td class="px-5 py-3.5 font-mono text-slate-400">#<?= $u['user_id'] ?></td>
              <td class="px-5 py-3.5 font-bold text-slate-900 dark:text-white">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 font-bold flex items-center justify-center text-xs">
                    <?= strtoupper(substr($u['name'] ?? 'U', 0, 1)) ?>
                  </div>
                  <span><?= htmlspecialchars($u['name']) ?></span>
                </div>
              </td>
              <td class="px-5 py-3.5 font-mono text-slate-600 dark:text-slate-400">
                <?= htmlspecialchars($u['username']) ?>
              </td>
              <td class="px-5 py-3.5">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $roleBadge ?>">
                  <?= htmlspecialchars(ucfirst($u['role'])) ?>
                </span>
              </td>
              <td class="px-5 py-3.5 text-right whitespace-nowrap">
                <button type="button" onclick='openEditUserModal(<?= json_encode($u) ?>)' class="p-1.5 text-slate-400 hover:text-indigo-600 dark:hover:text-indigo-400 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/40 transition-colors" title="Edit User">
                  <i data-lucide="edit-2" class="w-4 h-4"></i>
                </button>
                <?php if ((int)$u['user_id'] !== (int)$currentUser['user_id']): ?>
                  <form method="POST" action="index.php?page=users" onsubmit="return confirm('Delete user account <?= htmlspecialchars($u['username']) ?>?');" class="inline">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors" title="Delete">
                      <i data-lucide="trash-2" class="w-4 h-4"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ================= ADD USER MODAL ================= -->
<div id="add-user-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeAddUserModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Add Staff Account</h3>
        <button type="button" onclick="closeAddUserModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=users" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="create">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
          <input type="text" name="name" required placeholder="e.g. John Doe" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Username *</label>
          <input type="text" name="username" required placeholder="e.g. jdoe" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
          <select name="role" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
            <option value="Cashier">Cashier</option>
            <option value="Manager">Manager</option>
            <option value="Admin">Admin</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Password *</label>
          <input type="password" name="password" required placeholder="••••••••" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeAddUserModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Save Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ================= EDIT USER MODAL ================= -->
<div id="edit-user-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
  <div class="min-h-screen px-4 flex items-center justify-center">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" onclick="closeEditUserModal()"></div>
    <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-xl max-w-sm w-full p-6 border border-slate-200 dark:border-slate-700 z-10">
      <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100 dark:border-slate-700">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Edit Staff Account</h3>
        <button type="button" onclick="closeEditUserModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
          <i data-lucide="x" class="w-4 h-4"></i>
        </button>
      </div>

      <form method="POST" action="index.php?page=users" class="space-y-3.5 text-xs">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="user_id" id="edit-user-id">

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
          <input type="text" name="name" id="edit-user-name" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Username *</label>
          <input type="text" name="username" id="edit-user-username" required class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
          <select name="role" id="edit-user-role" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
            <option value="Cashier">Cashier</option>
            <option value="Manager">Manager</option>
            <option value="Admin">Admin</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">New Password (leave blank to keep current)</label>
          <input type="password" name="password" placeholder="••••••••" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 rounded-xl text-slate-800 dark:text-slate-100 text-xs focus:outline-none focus:border-indigo-500">
        </div>

        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-2">
          <button type="button" onclick="closeEditUserModal()" class="px-3.5 py-2 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700 rounded-xl font-medium">Cancel</button>
          <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-semibold shadow-xs">Update Account</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  function openAddUserModal() {
    document.getElementById('add-user-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeAddUserModal() {
    document.getElementById('add-user-modal').classList.add('hidden');
  }

  function openEditUserModal(user) {
    document.getElementById('edit-user-id').value = user.user_id;
    document.getElementById('edit-user-name').value = user.name;
    document.getElementById('edit-user-username').value = user.username;
    document.getElementById('edit-user-role').value = user.role;

    document.getElementById('edit-user-modal').classList.remove('hidden');
    if (window.lucide) window.lucide.createIcons();
  }
  function closeEditUserModal() {
    document.getElementById('edit-user-modal').classList.add('hidden');
  }
</script>

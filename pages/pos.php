<?php
/**
 * Laptop Store Management System
 * High-Speed Store POS Terminal (Light & Dark Mode)
 */
checkAuthRequired(['Admin', 'Manager', 'Cashier']);
$db = $pdo;

// Fetch products
$products = $db->query("SELECT * FROM products ORDER BY brand ASC, product_name ASC")->fetchAll();
// Fetch customers
$customers = $db->query("SELECT customer_id, name, phone FROM customers ORDER BY name ASC")->fetchAll();
// Fetch unique brands
$brands = array_values(array_unique(array_filter(array_column($products, 'brand'))));
?>

<div class="space-y-4" id="pos-root">
  <!-- POS Top Bar -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <div>
      <h2 class="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
        <i data-lucide="store" class="w-6 h-6 text-indigo-600 dark:text-indigo-400"></i>
        <span>Point of Sale Terminal</span>
      </h2>
      <p class="text-xs text-slate-500 dark:text-slate-400">Fast checkout register for in-store laptop purchases</p>
    </div>
  </div>

  <!-- Mobile View Switcher (Catalog vs Cart) -->
  <div class="flex lg:hidden bg-slate-200/80 dark:bg-slate-800 p-1 rounded-2xl gap-1">
    <button type="button" 
            id="tab-btn-catalog" 
            onclick="switchMobilePosTab('catalog')" 
            class="flex-1 py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs flex items-center justify-center gap-1.5 active:scale-95">
      <i data-lucide="laptop" class="w-4 h-4"></i>
      <span>Laptops Catalog</span>
    </button>
    <button type="button" 
            id="tab-btn-cart" 
            onclick="switchMobilePosTab('cart')" 
            class="flex-1 py-2 px-3 rounded-xl text-xs font-medium text-center transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center gap-1.5 active:scale-95">
      <i data-lucide="shopping-cart" class="w-4 h-4"></i>
      <span>Active Cart</span>
      <span id="mobile-cart-badge" class="ml-1 px-1.5 py-0.2 bg-emerald-500 text-white rounded-full text-[10px] font-bold">0</span>
    </button>
  </div>

  <!-- Floating Mobile Quick-Cart Bar (Appears when viewing catalog on mobile with items in cart) -->
  <div id="mobile-floating-cart-bar" class="hidden lg:hidden fixed bottom-20 left-3 right-3 z-20">
    <button type="button" onclick="switchMobilePosTab('cart')" class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 active:scale-[0.99] text-white font-bold rounded-2xl shadow-xl flex items-center justify-between text-xs sm:text-sm transition-all border border-emerald-500">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center">
          <i data-lucide="shopping-bag" class="w-4 h-4 text-white"></i>
        </div>
        <span id="mobile-floating-cart-count">0 items added</span>
      </div>
      <div class="flex items-center gap-1.5 font-mono">
        <span id="mobile-floating-cart-total" class="font-bold">₱0.00</span>
        <span class="text-[11px] font-sans bg-white/20 px-2 py-0.5 rounded-lg">View Cart &rarr;</span>
      </div>
    </button>
  </div>

  <!-- Main POS Grid: 7 cols Catalog, 5 cols Cart -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

    <!-- ================= LEFT: PRODUCT SEARCH & GRID (7 Cols) ================= -->
    <div id="pos-catalog-panel" class="lg:col-span-7 space-y-3">
      <!-- Search & Brand Filter Bar -->
      <div class="bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs space-y-3">
        <div class="relative">
          <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2"></i>
          <input type="text" id="pos-search" oninput="filterPosProducts()" placeholder="Search laptop model or brand..." class="w-full pl-10 pr-4 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 text-slate-900 dark:text-white placeholder:text-slate-400 dark:placeholder:text-slate-500 rounded-xl text-xs sm:text-sm focus:outline-none focus:border-indigo-500 transition-all">
        </div>

        <!-- Brand Filter Pills -->
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar pb-0.5">
          <button type="button" onclick="filterByBrand('')" class="pos-brand-pill px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-600 text-white shrink-0 transition-all">
            All Brands
          </button>
          <?php foreach ($brands as $b): ?>
            <button type="button" onclick="filterByBrand('<?= htmlspecialchars($b) ?>')" class="pos-brand-pill px-3 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 shrink-0 transition-all">
              <?= htmlspecialchars($b) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Laptop Products Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[calc(100vh-280px)] overflow-y-auto pr-1" id="pos-products-grid">
        <?php foreach ($products as $p): ?>
          <div class="pos-card bg-white dark:bg-slate-800 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs hover:border-indigo-400 dark:hover:border-indigo-500 transition-all flex flex-col justify-between"
               data-brand="<?= htmlspecialchars(strtolower($p['brand'] ?? '')) ?>"
               data-name="<?= htmlspecialchars(strtolower($p['product_name'] ?? '')) ?>"
               data-id="<?= $p['product_id'] ?>">
            <div>
              <div class="flex items-center justify-between mb-2">
                <?= getBrandBadge($p['brand'], 'sm') ?>
                <?= getStockBadge($p['stock']) ?>
              </div>

              <h4 class="text-sm font-bold text-slate-900 dark:text-white leading-snug"><?= htmlspecialchars($p['product_name']) ?></h4>
              <p class="text-[11px] text-slate-400 dark:text-slate-500 font-medium"><?= htmlspecialchars($p['category']) ?></p>

              <!-- Hardware Specs Preview -->
              <?= getSpecsPills($p['product_name'], $p['specifications']) ?>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-slate-700 flex items-center justify-between">
              <div>
                <span class="text-[10px] text-slate-400 dark:text-slate-500 block">Price</span>
                <span class="text-sm font-bold text-slate-900 dark:text-white"><?= formatCurrency($p['price']) ?></span>
              </div>

              <?php if ($p['stock'] > 0): ?>
                <button type="button" onclick='addToPosCart(<?= json_encode($p) ?>)' class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold shadow-xs flex items-center gap-1.5 transition-all active:scale-95">
                  <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                  <span>Add</span>
                </button>
              <?php else: ?>
                <span class="px-3 py-1.5 bg-slate-100 dark:bg-slate-700 text-slate-400 dark:text-slate-500 rounded-xl text-xs font-medium cursor-not-allowed">
                  Sold Out
                </span>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ================= RIGHT: CART & CHECKOUT PANEL (5 Cols) ================= -->
    <div id="pos-cart-panel" class="lg:col-span-5 space-y-3">
      <div class="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700 shadow-xs p-4 sm:p-5 flex flex-col h-full min-h-[560px]">

        <!-- Cart Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-700">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <i data-lucide="shopping-cart" class="w-4 h-4"></i>
            </div>
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white">Current Order</h3>
              <p class="text-[11px] text-slate-400 dark:text-slate-500"><span id="cart-items-count">0</span> item(s) in cart</p>
            </div>
          </div>
          <button type="button" onclick="clearPosCart()" class="text-xs text-slate-400 hover:text-rose-600 flex items-center gap-1 transition-colors">
            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
            <span>Clear</span>
          </button>
        </div>

        <!-- Customer Selector -->
        <div class="py-3 border-b border-slate-100 dark:border-slate-700">
          <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Customer</label>
          <div class="flex gap-2">
            <select id="pos-customer" class="flex-1 px-3 py-2 bg-slate-50 dark:bg-slate-700/50 border border-slate-200 dark:border-slate-600 text-slate-800 dark:text-slate-100 rounded-xl text-xs focus:outline-none focus:border-indigo-500">
              <option value="1">Walk-in Customer</option>
              <?php foreach ($customers as $c): ?>
                <option value="<?= $c['customer_id'] ?>"><?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['phone'] ?: 'No phone') ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <!-- Scrollable Cart Items List -->
        <div class="flex-1 overflow-y-auto py-3 space-y-2.5 max-h-[220px]" id="pos-cart-items">
          <div id="pos-empty-cart" class="text-center py-10 text-slate-400 dark:text-slate-500">
            <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
            <p class="text-xs font-medium">Cart is empty</p>
            <p class="text-[11px] text-slate-400 dark:text-slate-500">Select laptops from the catalog to begin sale</p>
          </div>
        </div>

        <!-- Pricing Summary -->
        <div class="pt-3 border-t border-slate-100 dark:border-slate-700 space-y-2 text-xs">
          <div class="flex justify-between text-slate-500 dark:text-slate-400">
            <span>Subtotal</span>
            <span class="font-semibold text-slate-800 dark:text-slate-200" id="pos-subtotal">₱0.00</span>
          </div>

          <!-- Discount Options -->
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400">
            <span>Discount (%)</span>
            <div class="flex items-center gap-1">
              <button type="button" onclick="setDiscount(0)" class="disc-btn px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400">0%</button>
              <button type="button" onclick="setDiscount(5)" class="disc-btn px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200">5%</button>
              <button type="button" onclick="setDiscount(10)" class="disc-btn px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200">10%</button>
            </div>
          </div>
          <div class="flex justify-between text-rose-600 dark:text-rose-400 font-medium">
            <span>Discount Amount</span>
            <span id="pos-discount">-₱0.00</span>
          </div>

          <!-- Payment Method Selector -->
          <div class="pt-2 border-t border-slate-100 dark:border-slate-700">
            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Payment Method</label>
            <div class="grid grid-cols-4 gap-1.5">
              <button type="button" onclick="setPaymentMethod('Cash')" class="pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-indigo-600 text-white flex flex-col items-center justify-center transition-all">
                <i data-lucide="banknote" class="w-3.5 h-3.5 mb-0.5"></i>
                <span class="text-[10px]">Cash</span>
              </button>
              <button type="button" onclick="setPaymentMethod('GCash')" class="pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 flex flex-col items-center justify-center transition-all">
                <i data-lucide="smartphone" class="w-3.5 h-3.5 mb-0.5"></i>
                <span class="text-[10px]">GCash</span>
              </button>
              <button type="button" onclick="setPaymentMethod('Maya')" class="pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 flex flex-col items-center justify-center transition-all">
                <i data-lucide="wallet" class="w-3.5 h-3.5 mb-0.5"></i>
                <span class="text-[10px]">Maya</span>
              </button>
              <button type="button" onclick="setPaymentMethod('Credit Card')" class="pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 flex flex-col items-center justify-center transition-all">
                <i data-lucide="credit-card" class="w-3.5 h-3.5 mb-0.5"></i>
                <span class="text-[10px]">Card</span>
              </button>
            </div>
          </div>

          <!-- Final Total -->
          <div class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-700 text-slate-900 dark:text-white">
            <div>
              <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Total Payable</span>
              <p class="text-xl font-extrabold text-indigo-600 dark:text-indigo-400" id="pos-total">₱0.00</p>
            </div>
            <span class="text-[10px] text-slate-400">VAT Included</span>
          </div>

          <!-- Checkout Button -->
          <button type="button" id="btn-complete-checkout" onclick="submitPosTransaction()" disabled class="w-full mt-2 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs sm:text-sm shadow-xs transition-all active:scale-[0.99] flex items-center justify-center gap-2">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            <span>Complete Sale & Print Receipt</span>
          </button>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
  let posCart = [];
  let currentDiscountPercent = 0;
  let currentPaymentMethod = 'Cash';
  let activeMobilePosTab = 'catalog';

  function switchMobilePosTab(tab) {
    activeMobilePosTab = tab;
    const catPanel = document.getElementById('pos-catalog-panel');
    const cartPanel = document.getElementById('pos-cart-panel');
    const tabCat = document.getElementById('tab-btn-catalog');
    const tabCart = document.getElementById('tab-btn-cart');
    const floatingBar = document.getElementById('mobile-floating-cart-bar');

    if (window.innerWidth < 1024) {
      if (tab === 'catalog') {
        if (catPanel) catPanel.classList.remove('hidden');
        if (cartPanel) cartPanel.classList.add('hidden');
        if (tabCat) tabCat.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs flex items-center justify-center gap-1.5 active:scale-95';
        if (tabCart) tabCart.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-medium text-center transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center gap-1.5 active:scale-95';
        if (posCart.length > 0 && floatingBar) floatingBar.classList.remove('hidden');
      } else {
        if (catPanel) catPanel.classList.add('hidden');
        if (cartPanel) cartPanel.classList.remove('hidden');
        if (tabCart) tabCart.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-bold text-center transition-all bg-white dark:bg-slate-700 text-indigo-600 dark:text-indigo-400 shadow-xs flex items-center justify-center gap-1.5 active:scale-95';
        if (tabCat) tabCat.className = 'flex-1 py-2 px-3 rounded-xl text-xs font-medium text-center transition-all text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white flex items-center justify-center gap-1.5 active:scale-95';
        if (floatingBar) floatingBar.classList.add('hidden');
      }
    }
    if (window.lucide) window.lucide.createIcons();
  }

  window.addEventListener('resize', () => {
    const catPanel = document.getElementById('pos-catalog-panel');
    const cartPanel = document.getElementById('pos-cart-panel');
    const floatingBar = document.getElementById('mobile-floating-cart-bar');
    if (window.innerWidth >= 1024) {
      if (catPanel) catPanel.classList.remove('hidden');
      if (cartPanel) cartPanel.classList.remove('hidden');
      if (floatingBar) floatingBar.classList.add('hidden');
    } else {
      switchMobilePosTab(activeMobilePosTab);
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    if (window.innerWidth < 1024) {
      switchMobilePosTab('catalog');
    }
  });

  function filterPosProducts() {
    const term = (document.getElementById('pos-search').value || '').toLowerCase().trim();
    const cards = document.querySelectorAll('.pos-card');
    cards.forEach(card => {
      const name = card.getAttribute('data-name');
      const brand = card.getAttribute('data-brand');
      if (name.includes(term) || brand.includes(term)) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function filterByBrand(brand) {
    const isDark = document.documentElement.classList.contains('dark');
    const buttons = document.querySelectorAll('.pos-brand-pill');
    buttons.forEach(btn => {
      if ((brand === '' && btn.innerText.includes('All Brands')) || btn.innerText.trim().toLowerCase() === brand.toLowerCase()) {
        btn.className = 'pos-brand-pill px-3 py-1 rounded-lg text-xs font-semibold bg-indigo-600 text-white shrink-0 transition-all';
      } else {
        btn.className = 'pos-brand-pill px-3 py-1 rounded-lg text-xs font-medium bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 shrink-0 transition-all';
      }
    });

    const cards = document.querySelectorAll('.pos-card');
    cards.forEach(card => {
      const cBrand = card.getAttribute('data-brand');
      if (!brand || cBrand.includes(brand.toLowerCase())) {
        card.style.display = 'flex';
      } else {
        card.style.display = 'none';
      }
    });
  }

  function addToPosCart(product) {
    const existing = posCart.find(i => i.product_id === product.product_id);
    if (existing) {
      if (existing.quantity >= product.stock) {
        showToast('warning', `Maximum available stock reached (${product.stock} units)`);
        return;
      }
      existing.quantity += 1;
    } else {
      posCart.push({
        product_id: product.product_id,
        product_name: product.product_name,
        brand: product.brand,
        price: parseFloat(product.price),
        maxStock: parseInt(product.stock),
        quantity: 1
      });
    }

    renderPosCart();
    showToast('info', `Added ${product.product_name} to cart`);
  }

  function updateQuantity(productId, delta) {
    const item = posCart.find(i => i.product_id === productId);
    if (!item) return;

    item.quantity += delta;
    if (item.quantity <= 0) {
      posCart = posCart.filter(i => i.product_id !== productId);
    } else if (item.quantity > item.maxStock) {
      item.quantity = item.maxStock;
      showToast('warning', `Maximum available units is ${item.maxStock}`);
    }
    renderPosCart();
  }

  function removeFromPosCart(productId) {
    posCart = posCart.filter(i => i.product_id !== productId);
    renderPosCart();
  }

  function clearPosCart() {
    posCart = [];
    renderPosCart();
  }

  function setDiscount(percent) {
    currentDiscountPercent = percent;
    document.querySelectorAll('.disc-btn').forEach(btn => {
      if (btn.innerText.includes(percent + '%')) {
        btn.className = 'disc-btn px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400';
      } else {
        btn.className = 'disc-btn px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200';
      }
    });
    renderPosCart();
  }

  function setPaymentMethod(method) {
    currentPaymentMethod = method;
    document.querySelectorAll('.pay-btn').forEach(btn => {
      if (btn.innerText.includes(method)) {
        btn.className = 'pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-indigo-600 text-white flex flex-col items-center justify-center transition-all';
      } else {
        btn.className = 'pay-btn py-1.5 px-2 rounded-xl text-xs font-semibold bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-600 flex flex-col items-center justify-center transition-all';
      }
    });
  }

  function computeCartSubtotal() {
    return posCart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  }

  function renderPosCart() {
    const container = document.getElementById('pos-cart-items');
    const checkoutBtn = document.getElementById('btn-complete-checkout');
    const countLabel = document.getElementById('cart-items-count');

    const totalQty = posCart.reduce((s, i) => s + i.quantity, 0);
    countLabel.innerText = totalQty;

    if (posCart.length === 0) {
      container.innerHTML = `
        <div id="pos-empty-cart" class="text-center py-10 text-slate-400 dark:text-slate-500">
          <i data-lucide="shopping-bag" class="w-8 h-8 mx-auto mb-2 text-slate-300 dark:text-slate-600"></i>
          <p class="text-xs font-medium">Cart is empty</p>
          <p class="text-[11px] text-slate-400 dark:text-slate-500">Select laptops from the catalog to begin sale</p>
        </div>`;
      checkoutBtn.disabled = true;
    } else {
      let html = '';
      posCart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        html += `
          <div class="p-2.5 rounded-xl border border-slate-100 dark:border-slate-700/80 bg-slate-50 dark:bg-slate-700/40 flex items-center justify-between text-xs">
            <div class="truncate mr-2 flex-1">
              <p class="font-bold text-slate-900 dark:text-white truncate">${item.product_name}</p>
              <span class="text-[10px] text-slate-500 dark:text-slate-400 font-medium">${item.brand} &bull; ${formatPeso(item.price)}</span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <div class="flex items-center border border-slate-200 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 overflow-hidden">
                <button type="button" onclick="updateQuantity(${item.product_id}, -1)" class="px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300">-</button>
                <span class="px-2 py-1 font-bold text-slate-800 dark:text-slate-100 text-[11px]">${item.quantity}</span>
                <button type="button" onclick="updateQuantity(${item.product_id}, 1)" class="px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300">+</button>
              </div>
              <span class="font-bold text-slate-900 dark:text-white w-16 text-right">${formatPeso(itemTotal)}</span>
              <button type="button" onclick="removeFromPosCart(${item.product_id})" class="text-slate-400 hover:text-rose-600 p-1">
                <i data-lucide="x" class="w-3.5 h-3.5"></i>
              </button>
            </div>
          </div>
        `;
      });
      container.innerHTML = html;
      checkoutBtn.disabled = false;
    }

    const subtotal = computeCartSubtotal();
    const discountAmount = subtotal * (currentDiscountPercent / 100);
    const totalAmount = subtotal - discountAmount;

    document.getElementById('pos-subtotal').innerText = formatPeso(subtotal);
    document.getElementById('pos-discount').innerText = '-' + formatPeso(discountAmount);
    document.getElementById('pos-total').innerText = formatPeso(totalAmount);

    // Update mobile cart badge & floating quick-checkout bar
    const mobileBadge = document.getElementById('mobile-cart-badge');
    const mobileFloatingBar = document.getElementById('mobile-floating-cart-bar');
    const mobileFloatingCount = document.getElementById('mobile-floating-cart-count');
    const mobileFloatingTotal = document.getElementById('mobile-floating-cart-total');

    if (mobileBadge) mobileBadge.innerText = totalQty;
    if (mobileFloatingCount) mobileFloatingCount.innerText = `${totalQty} item${totalQty === 1 ? '' : 's'} in cart`;
    if (mobileFloatingTotal) mobileFloatingTotal.innerText = formatPeso(totalAmount);

    if (mobileFloatingBar) {
      if (window.innerWidth < 1024 && totalQty > 0 && activeMobilePosTab === 'catalog') {
        mobileFloatingBar.classList.remove('hidden');
      } else {
        mobileFloatingBar.classList.add('hidden');
      }
    }

    if (window.lucide) window.lucide.createIcons();
  }

  async function submitPosTransaction() {
    if (posCart.length === 0) return;

    const btn = document.getElementById('btn-complete-checkout');
    btn.disabled = true;
    btn.innerHTML = '<span class="animate-spin inline-block mr-2">&#9696;</span> Processing...';

    const customerId = document.getElementById('pos-customer').value;
    const subtotal = computeCartSubtotal();
    const discountAmount = subtotal * (currentDiscountPercent / 100);
    const totalAmount = subtotal - discountAmount;

    try {
      for (const item of posCart) {
        const payload = {
          customer_id: customerId,
          product_id: item.product_id,
          quantity: item.quantity,
          discount_amount: (item.price * item.quantity) * (currentDiscountPercent / 100),
          payment_method: currentPaymentMethod,
          payment_status: 'Paid',
          order_type: 'pos',
          order_status: 'Delivered',
        };

        const res = await fetch('api/sales/create.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Transaction error');
      }

      const lastItem = posCart[0];
      const custSelect = document.getElementById('pos-customer');
      const saleReceiptData = {
        sale_id: Math.floor(10000 + Math.random() * 90000),
        customer_name: custSelect.options[custSelect.selectedIndex].text,
        brand: lastItem.brand + (posCart.length > 1 ? ` (+${posCart.length - 1} more items)` : ''),
        product_name: lastItem.product_name,
        quantity: posCart.reduce((s, i) => s + i.quantity, 0),
        price: lastItem.price,
        subtotal: subtotal,
        discount_amount: discountAmount,
        total_amount: totalAmount,
        payment_method: currentPaymentMethod,
        payment_status: 'Paid',
        created_at: new Date().toLocaleString()
      };

      showToast('success', 'Sale transaction completed successfully!');
      clearPosCart();
      openReceiptModal(saleReceiptData);

    } catch (err) {
      showToast('error', err.message || 'Failed to complete transaction');
    } finally {
      btn.disabled = false;
      btn.innerHTML = '<i data-lucide="check-circle" class="w-4 h-4"></i><span>Complete Sale & Print Receipt</span>';
      if (window.lucide) window.lucide.createIcons();
    }
  }
</script>

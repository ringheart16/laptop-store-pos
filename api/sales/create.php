<?php
/**
 * Create Sale (POS Transaction)
 * POST /api/sales/create.php
 * 
 * Uses database transaction to ensure sale + stock update succeed together.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../middleware/auth.php';

requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$input = getJsonInput();

// Validate required fields
$customerId = !empty($input['customer_id']) ? (int)$input['customer_id'] : null;
$customerName = trim($input['buyer_name'] ?? ($input['customer_name'] ?? ''));
$customerPhone = trim($input['phone'] ?? ($input['customer_phone'] ?? ''));
$customerEmail = trim($input['email'] ?? ($input['customer_email'] ?? ''));
$deliveryAddress = trim($input['delivery_address'] ?? '');

$user = getCurrentUser();
if (!$customerId && empty($customerName) && $user) {
    $customerName = $user['name'];
}

if (!$customerId && empty($customerName)) {
    jsonError('Buyer / Customer name is required');
}
if (empty($input['product_id'])) jsonError('Product is required');
if (empty($input['quantity']) || $input['quantity'] <= 0) jsonError('Quantity must be greater than 0');

// Validate payment method
$validPaymentMethods = ['Cash', 'GCash', 'Credit Card', 'Bank Transfer', 'Maya', 'Cash on Delivery'];
$paymentMethod = trim($input['payment_method'] ?? 'Cash');
if (!in_array($paymentMethod, $validPaymentMethods)) {
    jsonError('Invalid payment method. Accepted: ' . implode(', ', $validPaymentMethods));
}

$productId = (int)$input['product_id'];
$quantity = (int)$input['quantity'];
$discountAmount = max(0, (float)($input['discount_amount'] ?? 0.00));
$paymentReference = trim($input['payment_reference'] ?? '');

// Order type & statuses
$orderType = (!empty($input['order_type']) && in_array(strtolower($input['order_type']), ['online', 'pos'])) 
    ? strtolower($input['order_type']) 
    : (($user && strtolower($user['role']) === 'customer') ? 'online' : 'pos');

if ($orderType === 'online') {
    // Online orders start as Pending, unless verified
    $paymentStatus = !empty($input['payment_status']) ? trim($input['payment_status']) : ($paymentReference ? 'Processing' : 'Pending');
    $orderStatus = !empty($input['order_status']) ? trim($input['order_status']) : 'Pending';
} else {
    // In-store POS transactions are Paid and Delivered on the spot
    $paymentStatus = !empty($input['payment_status']) ? trim($input['payment_status']) : 'Paid';
    $orderStatus = !empty($input['order_status']) ? trim($input['order_status']) : 'Delivered';
}

try {
    // If buyer name was provided directly instead of ID, find or auto-create customer
    if (!$customerId) {
        $stmt = $pdo->prepare("SELECT customer_id, name, phone, email FROM customers WHERE name = ? LIMIT 1");
        $stmt->execute([$customerName]);
        $existing = $stmt->fetch();
        if ($existing) {
            $customerId = (int)$existing['customer_id'];
            $customer = $existing;
        } else {
            $stmt = $pdo->prepare("INSERT INTO customers (name, phone, email) VALUES (?, ?, ?)");
            $stmt->execute([$customerName, $customerPhone, $customerEmail]);
            $customerId = (int)$pdo->lastInsertId();
            $customer = ['customer_id' => $customerId, 'name' => $customerName, 'phone' => $customerPhone, 'email' => $customerEmail];
        }
    } else {
        // Verify customer exists by ID
        $stmt = $pdo->prepare("SELECT customer_id, name, phone, email FROM customers WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch();
        if (!$customer) jsonError('Customer record not found');
    }

    // Verify product exists and has sufficient stock
    $stmt = $pdo->prepare("SELECT product_id, product_name, brand, price, stock, category FROM products WHERE product_id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) jsonError('Product not found');
    if ($product['stock'] < $quantity) {
        jsonError("Insufficient stock. Available: {$product['stock']}, Requested: {$quantity}");
    }

    // Calculate total
    $subtotal = $product['price'] * $quantity;
    $totalAmount = max(0, $subtotal - $discountAmount);
    $saleDate = date('Y-m-d');
    $now = date('Y-m-d H:i:s');

    // Generate hardware Serial Number
    $brandPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $product['brand'] ?: 'LAP'), 0, 4));
    $serialNumber = 'SN-' . $brandPrefix . '-' . mt_rand(100000, 999999);
    $warrantyInfo = '1 Year Official Manufacturer Warranty + 7 Days Replacement Policy';

    // Begin transaction
    $pdo->beginTransaction();

    // Insert sale record with complete e-commerce fields
    $stmt = $pdo->prepare("
        INSERT INTO sales (
            customer_id, product_id, quantity, total_amount, discount_amount,
            payment_method, payment_status, payment_reference,
            order_type, order_status, delivery_address, serial_number, warranty_info,
            sale_date, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $customerId, $productId, $quantity, $totalAmount, $discountAmount,
        $paymentMethod, $paymentStatus, $paymentReference,
        $orderType, $orderStatus, $deliveryAddress, $serialNumber, $warrantyInfo,
        $saleDate, $now
    ]);
    $saleId = $pdo->lastInsertId();

    // Decrease product stock
    $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE product_id = ? AND stock >= ?");
    $stmt->execute([$quantity, $productId, $quantity]);

    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        jsonError('Failed to update stock. Possible race condition.');
    }

    // Sync inventory stock
    $stmt = $pdo->prepare("
        UPDATE inventory SET stock = stock - ?, last_updated = CURDATE()
        WHERE product_id = ?
    ");
    $stmt->execute([$quantity, $productId]);

    $pdo->commit();

    jsonSuccess([
        'sale_id' => (int)$saleId,
        'sale' => [
            'sale_id' => (int)$saleId,
            'customer_name' => $customer['name'],
            'customer_phone' => $customer['phone'] ?? $customerPhone,
            'customer_email' => $customer['email'] ?? $customerEmail,
            'product_name' => $product['product_name'],
            'brand' => $product['brand'],
            'quantity' => $quantity,
            'unit_price' => (float)$product['price'],
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $totalAmount,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentStatus,
            'payment_reference' => $paymentReference,
            'order_type' => $orderType,
            'order_status' => $orderStatus,
            'delivery_address' => $deliveryAddress,
            'serial_number' => $serialNumber,
            'warranty_info' => $warrantyInfo,
            'sale_date' => $saleDate,
            'created_at' => $now
        ]
    ], $orderType === 'online' ? 'Order submitted successfully!' : 'POS sale completed successfully');

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonError('Failed to create transaction: ' . $e->getMessage(), 500);
}

<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_payment'])) {
    $order_ids = $_POST['order_ids'] ?? [];
    $table_id = $_POST['table_id'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    if (!empty($order_ids)) {
        try {
            $db->beginTransaction();
            
            // Update all orders to completed (paid)
            $placeholders = str_repeat('?,', count($order_ids) - 1) . '?';
            $query = "UPDATE orders SET status = 'completed', paid_at = NOW() WHERE id IN ($placeholders)";
            $stmt = $db->prepare($query);
            $stmt->execute($order_ids);
            
            // Generate new session token for security
            $new_token = bin2hex(random_bytes(16));
            
            // Update table status and regenerate token
            $query = "UPDATE tables SET status = 'available', session_token = ? WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$new_token, $table_id]);
            
            $db->commit();
            
            header('Location: checkout.php?success=1');
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// Get table ID
$table_id = $_GET['table'] ?? 0;

if (empty($table_id)) {
    header('Location: tables.php');
    exit;
}

// Get table info
$query = "SELECT * FROM tables WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $table_id);
$stmt->execute();
$table = $stmt->fetch();

if (!$table) {
    header('Location: tables.php');
    exit;
}

// Get unpaid orders for this table
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o
          WHERE o.table_id = :table_id 
          AND o.status IN ('pending', 'preparing', 'ready', 'served')
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':table_id', $table_id);
$stmt->execute();
$orders = $stmt->fetchAll();

// Calculate total
$total_amount = array_sum(array_column($orders, 'total_amount'));

$page_title = 'เช็คบิล - โต๊ะ ' . $table['table_number'];
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="receipt"></i>
                    เช็คบิล - โต๊ะ <?php echo htmlspecialchars($table['table_number']); ?>
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="tables.php">จัดการโต๊ะ</a></li>
                        <li class="breadcrumb-item active">เช็คบิล</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i data-lucide="check-circle" class="icon-sm"></i>
                <strong>ชำระเงินเรียบร้อยแล้ว!</strong> โต๊ะว่างพร้อมใช้งาน และ <strong>QR Code เดิมถูกยกเลิกแล้ว</strong> (Token ใหม่ถูกสร้างเพื่อความปลอดภัย)
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i data-lucide="alert-circle" class="icon-sm"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Bill Details -->
            <div class="col-lg-8">
                <?php if (empty($orders)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i data-lucide="inbox" style="width: 80px; height: 80px; opacity: 0.3; color: var(--primary-color);"></i>
                            <h4 class="mt-3 text-muted">ไม่มีออเดอร์ค้างชำระ</h4>
                            <p class="text-muted">โต๊ะนี้ไม่มีรายการอาหารที่ต้องชำระเงิน</p>
                            <a href="tables.php" class="btn btn-primary mt-3">
                                <i data-lucide="arrow-left"></i>
                                กลับไปหน้าจัดการโต๊ะ
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i data-lucide="list"></i>
                                รายการออเดอร์
                                <span class="badge bg-secondary ms-2"><?php echo count($orders); ?> ออเดอร์</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($orders as $order): ?>
                                <?php
                                // Get order items
                                $query = "SELECT oi.*, mi.name, mi.image
                                          FROM order_items oi
                                          JOIN menu_items mi ON oi.menu_item_id = mi.id
                                          WHERE oi.order_id = :order_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':order_id', $order['id']);
                                $stmt->execute();
                                $items = $stmt->fetchAll();
                                ?>
                                
                                <div class="card mb-3 border">
                                    <div class="card-header bg-light">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                                <span class="text-muted ms-2">
                                                    <i data-lucide="clock" class="icon-sm"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                                </span>
                                            </div>
                                            <?php
                                            $status_text = [
                                                'pending' => 'รอดำเนินการ',
                                                'preparing' => 'กำลังปรุง',
                                                'ready' => 'พร้อมเสิร์ฟ',
                                                'served' => 'เสิร์ฟแล้ว'
                                            ];
                                            ?>
                                            <span class="badge status-<?php echo $order['status']; ?>">
                                                <?php echo $status_text[$order['status']] ?? $order['status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <table class="table table-sm mb-0">
                                            <tbody>
                                                <?php foreach ($items as $item): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                        <td class="text-center" width="80">
                                                            <span class="badge bg-primary"><?php echo $item['quantity']; ?></span>
                                                        </td>
                                                        <td class="text-end" width="100">
                                                            ฿<?php echo number_format($item['price'], 2); ?>
                                                        </td>
                                                        <td class="text-end fw-bold" width="120">
                                                            ฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <tr class="table-active">
                                                    <td colspan="3" class="text-end"><strong>รวม:</strong></td>
                                                    <td class="text-end fw-bold text-primary">
                                                        ฿<?php echo number_format($order['total_amount'], 2); ?>
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Payment Summary -->
            <?php if (!empty($orders)): ?>
                <div class="col-lg-4">
                    <div class="card fade-in sticky-top" style="top: 20px; animation-delay: 0.1s;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i data-lucide="calculator"></i>
                                สรุปยอดชำระ
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>โต๊ะ:</span>
                                    <strong><?php echo htmlspecialchars($table['table_number']); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>จำนวนออเดอร์:</span>
                                    <strong><?php echo count($orders); ?> ออเดอร์</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>จำนวนรายการ:</span>
                                    <strong><?php echo array_sum(array_column($orders, 'item_count')); ?> รายการ</strong>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>ยอดรวม:</span>
                                    <h5 class="mb-0">฿<?php echo number_format($total_amount, 2); ?></h5>
                                </div>
                            </div>
                            
                            <form method="POST" id="paymentForm">
                                <?php foreach ($orders as $order): ?>
                                    <input type="hidden" name="order_ids[]" value="<?php echo $order['id']; ?>">
                                <?php endforeach; ?>
                                <input type="hidden" name="table_id" value="<?php echo $table['id']; ?>">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-600">
                                        <i data-lucide="credit-card" class="icon-sm"></i>
                                        ช่องทางชำระเงิน
                                    </label>
                                    <select class="form-select" name="payment_method" required>
                                        <option value="cash">เงินสด</option>
                                        <option value="card">บัตรเครดิต/เดบิต</option>
                                        <option value="promptpay">พร้อมเพย์</option>
                                        <option value="transfer">โอนเงิน</option>
                                    </select>
                                </div>
                                
                                <button type="submit" name="complete_payment" class="btn btn-success w-100 btn-lg" 
                                        onclick="return confirm('ยืนยันการชำระเงินจำนวน ฿<?php echo number_format($total_amount, 2); ?> ?')">
                                    <i data-lucide="check-circle"></i>
                                    ชำระเงิน ฿<?php echo number_format($total_amount, 2); ?>
                                </button>
                            </form>
                            
                            <a href="tables.php" class="btn btn-outline-secondary w-100 mt-2">
                                <i data-lucide="arrow-left"></i>
                                ยกเลิก
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

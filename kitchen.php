<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('chef');

$database = new Database();
$db = $database->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Update order status
        $query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $new_status);
        $stmt->bindParam(':id', $order_id);
        $stmt->execute();
        
        // If changing to preparing, disable cancellation for all items in this order
        if ($new_status === 'preparing') {
            $query = "UPDATE order_items SET can_cancel = FALSE WHERE order_id = :order_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':order_id', $order_id);
            $stmt->execute();
        }
        
        $db->commit();
        header('Location: kitchen.php?success=1');
        exit;
        
    } catch (PDOException $e) {
        $db->rollBack();
        header('Location: kitchen.php?error=1');
        exit;
    }
}

// Get orders by status
$statuses = ['pending', 'preparing', 'ready'];
$orders_by_status = [];

foreach ($statuses as $status) {
    $query = "SELECT o.*, t.table_number,
              (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
              FROM orders o
              JOIN tables t ON o.table_id = t.id
              WHERE o.status = :status
              ORDER BY o.created_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->execute();
    $orders_by_status[$status] = $stmt->fetchAll();
}

$page_title = 'Kitchen Display - Chef';
$extra_js = ['/restaurant-qrcode/assets/js/kitchen.js'];
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold">
                    <i data-lucide="chef-hat"></i>
                    Kitchen Display System
                </h2>
                <p class="text-muted">จัดการและอัปเดตสถานะออเดอร์</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-primary" onclick="location.reload()">
                    <i data-lucide="refresh-cw"></i>
                    รีเฟรช
                </button>
                <span class="badge bg-secondary ms-2">อัปเดตอัตโนมัติทุก 30 วินาที</span>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i data-lucide="check-circle" class="icon-sm"></i>
                อัปเดตสถานะสำเร็จ
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row g-4">
            <!-- Pending Orders -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="mb-0">
                            <i data-lucide="clock"></i>
                            รอดำเนินการ
                            <span class="badge bg-light text-dark ms-2"><?php echo count($orders_by_status['pending']); ?></span>
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
                        <?php if (empty($orders_by_status['pending'])): ?>
                            <div class="text-center py-5 text-muted">
                                <i data-lucide="inbox" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                                <p class="mt-3">ไม่มีออเดอร์ใหม่</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders_by_status['pending'] as $order): ?>
                                <?php
                                // Get order items
                                $query = "SELECT oi.*, mi.name 
                                          FROM order_items oi
                                          JOIN menu_items mi ON oi.menu_item_id = mi.id
                                          WHERE oi.order_id = :order_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':order_id', $order['id']);
                                $stmt->execute();
                                $items = $stmt->fetchAll();
                                
                                $time_ago = time() - strtotime($order['created_at']);
                                $minutes = floor($time_ago / 60);
                                ?>
                                <div class="card kitchen-order-card mb-3 fade-in">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold mb-1">
                                                    <i data-lucide="hash" class="icon-sm"></i>
                                                    #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <i data-lucide="grid-2x2" class="icon-sm"></i>
                                                    โต๊ะ <?php echo htmlspecialchars($order['table_number']); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-danger"><?php echo $minutes; ?> นาที</span>
                                        </div>
                                        
                                        <?php if (!empty($order['customer_name'])): ?>
                                            <p class="small text-muted mb-2">
                                                <i data-lucide="user" class="icon-sm"></i>
                                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        
                                        <div class="mb-3">
                                            <?php foreach ($items as $item): ?>
                                                <?php 
                                                    $cancelled_qty = $item['cancelled_quantity'] ?? 0;
                                                    $active_qty = $item['quantity'] - $cancelled_qty;
                                                    $is_cancelled = $cancelled_qty > 0;
                                                ?>
                                                <?php if ($is_cancelled): ?>
                                                    <!-- Cancelled items with strikethrough -->
                                                    <div class="d-flex justify-content-between mb-1 text-decoration-line-through text-muted">
                                                        <span>
                                                            <strong class="text-danger"><?php echo $cancelled_qty; ?>x</strong>
                                                            <small><?php echo htmlspecialchars($item['name']); ?> (ยกเลิก)</small>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($active_qty > 0): ?>
                                                    <!-- Active items -->
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>
                                                            <strong><?php echo $active_qty; ?>x</strong>
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <form method="POST" class="d-grid">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="preparing">
                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                <i data-lucide="chef-hat"></i>
                                                เริ่มปรุง
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Preparing Orders -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header" style="background-color: #3498DB; color: white;">
                        <h5 class="mb-0">
                            <i data-lucide="flame"></i>
                            กำลังปรุง
                            <span class="badge bg-light text-dark ms-2"><?php echo count($orders_by_status['preparing']); ?></span>
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
                        <?php if (empty($orders_by_status['preparing'])): ?>
                            <div class="text-center py-5 text-muted">
                                <i data-lucide="inbox" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                                <p class="mt-3">ไม่มีออเดอร์ที่กำลังปรุง</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders_by_status['preparing'] as $order): ?>
                                <?php
                                $query = "SELECT oi.*, mi.name 
                                          FROM order_items oi
                                          JOIN menu_items mi ON oi.menu_item_id = mi.id
                                          WHERE oi.order_id = :order_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':order_id', $order['id']);
                                $stmt->execute();
                                $items = $stmt->fetchAll();
                                
                                $time_ago = time() - strtotime($order['updated_at']);
                                $minutes = floor($time_ago / 60);
                                ?>
                                <div class="card kitchen-order-card preparing mb-3 fade-in">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold mb-1">
                                                    <i data-lucide="hash" class="icon-sm"></i>
                                                    #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <i data-lucide="grid-2x2" class="icon-sm"></i>
                                                    โต๊ะ <?php echo htmlspecialchars($order['table_number']); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-info"><?php echo $minutes; ?> นาที</span>
                                        </div>
                                        
                                        <?php if (!empty($order['customer_name'])): ?>
                                            <p class="small text-muted mb-2">
                                                <i data-lucide="user" class="icon-sm"></i>
                                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        
                                        <div class="mb-3">
                                            <?php foreach ($items as $item): ?>
                                                <?php 
                                                    $cancelled_qty = $item['cancelled_quantity'] ?? 0;
                                                    $active_qty = $item['quantity'] - $cancelled_qty;
                                                    $is_cancelled = $cancelled_qty > 0;
                                                ?>
                                                <?php if ($is_cancelled): ?>
                                                    <!-- Cancelled items with strikethrough -->
                                                    <div class="d-flex justify-content-between mb-1 text-decoration-line-through text-muted">
                                                        <span>
                                                            <strong class="text-danger"><?php echo $cancelled_qty; ?>x</strong>
                                                            <small><?php echo htmlspecialchars($item['name']); ?> (ยกเลิก)</small>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($active_qty > 0): ?>
                                                    <!-- Active items -->
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>
                                                            <strong><?php echo $active_qty; ?>x</strong>
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <form method="POST" class="d-grid">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="ready">
                                            <button type="submit" name="update_status" class="btn btn-success">
                                                <i data-lucide="check-circle"></i>
                                                พร้อมเสิร์ฟ
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Ready Orders -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i data-lucide="check-circle"></i>
                            พร้อมเสิร์ฟ
                            <span class="badge bg-light text-dark ms-2"><?php echo count($orders_by_status['ready']); ?></span>
                        </h5>
                    </div>
                    <div class="card-body" style="max-height: 80vh; overflow-y: auto;">
                        <?php if (empty($orders_by_status['ready'])): ?>
                            <div class="text-center py-5 text-muted">
                                <i data-lucide="inbox" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                                <p class="mt-3">ไม่มีออเดอร์พร้อมเสิร์ฟ</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($orders_by_status['ready'] as $order): ?>
                                <?php
                                $query = "SELECT oi.*, mi.name 
                                          FROM order_items oi
                                          JOIN menu_items mi ON oi.menu_item_id = mi.id
                                          WHERE oi.order_id = :order_id";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(':order_id', $order['id']);
                                $stmt->execute();
                                $items = $stmt->fetchAll();
                                
                                $time_ago = time() - strtotime($order['updated_at']);
                                $minutes = floor($time_ago / 60);
                                ?>
                                <div class="card kitchen-order-card ready mb-3 fade-in">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h5 class="fw-bold mb-1">
                                                    <i data-lucide="hash" class="icon-sm"></i>
                                                    #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <i data-lucide="grid-2x2" class="icon-sm"></i>
                                                    โต๊ะ <?php echo htmlspecialchars($order['table_number']); ?>
                                                </p>
                                            </div>
                                            <span class="badge bg-warning"><?php echo $minutes; ?> นาที</span>
                                        </div>
                                        
                                        <?php if (!empty($order['customer_name'])): ?>
                                            <p class="small text-muted mb-2">
                                                <i data-lucide="user" class="icon-sm"></i>
                                                <?php echo htmlspecialchars($order['customer_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <hr>
                                        
                                        <div class="mb-3">
                                            <?php foreach ($items as $item): ?>
                                                <?php 
                                                    $cancelled_qty = $item['cancelled_quantity'] ?? 0;
                                                    $active_qty = $item['quantity'] - $cancelled_qty;
                                                    $is_cancelled = $cancelled_qty > 0;
                                                ?>
                                                <?php if ($is_cancelled): ?>
                                                    <!-- Cancelled items with strikethrough -->
                                                    <div class="d-flex justify-content-between mb-1 text-decoration-line-through text-muted">
                                                        <span>
                                                            <strong class="text-danger"><?php echo $cancelled_qty; ?>x</strong>
                                                            <small><?php echo htmlspecialchars($item['name']); ?> (ยกเลิก)</small>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($active_qty > 0): ?>
                                                    <!-- Active items -->
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span>
                                                            <strong><?php echo $active_qty; ?>x</strong>
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <form method="POST" class="d-grid">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="status" value="served">
                                            <button type="submit" name="update_status" class="btn btn-secondary">
                                                <i data-lucide="check"></i>
                                                เสิร์ฟแล้ว
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

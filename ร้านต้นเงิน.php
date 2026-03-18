<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total orders today
$query = "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()";
$stmt = $db->query($query);
$stats['orders_today'] = $stmt->fetch()['count'];

// Total revenue today
$query = "SELECT COALESCE(SUM(total_amount), 0) as revenue FROM orders 
          WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'";
$stmt = $db->query($query);
$stats['revenue_today'] = $stmt->fetch()['revenue'];

// Pending orders
$query = "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'";
$stmt = $db->query($query);
$stats['pending_orders'] = $stmt->fetch()['count'];

// Active tables
$query = "SELECT COUNT(*) as count FROM tables WHERE status = 'occupied'";
$stmt = $db->query($query);
$stats['active_tables'] = $stmt->fetch()['count'];

// Recent orders
$query = "SELECT o.*, t.table_number, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o
          JOIN tables t ON o.table_id = t.id
          ORDER BY o.created_at DESC
          LIMIT 10";
$stmt = $db->query($query);
$recent_orders = $stmt->fetchAll();

$page_title = 'Dashboard - Admin';
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="layout-dashboard"></i>
                    Dashboard
                </h2>
                <p class="text-muted">ภาพรวมระบบร้านอาหาร</p>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card fade-in" style="border-left-color: var(--primary-color);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">ออเดอร์วันนี้</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($stats['orders_today']); ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(230, 126, 34, 0.1);">
                                <i data-lucide="shopping-cart" style="color: var(--primary-color);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card fade-in" style="border-left-color: var(--success-color); animation-delay: 0.1s;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">รายได้วันนี้</p>
                                <h3 class="fw-bold mb-0">฿<?php echo number_format($stats['revenue_today'], 2); ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(39, 174, 96, 0.1);">
                                <i data-lucide="dollar-sign" style="color: var(--success-color);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card fade-in" style="border-left-color: var(--warning-color); animation-delay: 0.2s;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">ออเดอร์รอดำเนินการ</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($stats['pending_orders']); ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(243, 156, 18, 0.1);">
                                <i data-lucide="clock" style="color: var(--warning-color);"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stat-card fade-in" style="border-left-color: #3498DB; animation-delay: 0.3s;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1">โต๊ะที่ใช้งาน</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($stats['active_tables']); ?></h3>
                            </div>
                            <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1);">
                                <i data-lucide="grid-2x2" style="color: #3498DB;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="row">
            <div class="col-12">
                <div class="card fade-in" style="animation-delay: 0.4s;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="list"></i>
                            ออเดอร์ล่าสุด
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-5 text-muted">
                                <i data-lucide="inbox" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                                <p class="mt-3">ยังไม่มีออเดอร์</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>โต๊ะ</th>
                                            <th>จำนวนรายการ</th>
                                            <th>ยอดเงิน</th>
                                            <th>สถานะ</th>
                                            <th>เวลา</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td>
                                                    <i data-lucide="hash" class="icon-sm"></i>
                                                    <?php echo htmlspecialchars($order['table_number']); ?>
                                                </td>
                                                <td><?php echo $order['item_count']; ?> รายการ</td>
                                                <td class="fw-bold text-success">฿<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <?php
                                                    $status_text = [
                                                        'pending' => 'รอดำเนินการ',
                                                        'preparing' => 'กำลังปรุง',
                                                        'ready' => 'พร้อมเสิร์ฟ',
                                                        'served' => 'เสิร์ฟแล้ว',
                                                        'cancelled' => 'ยกเลิก'
                                                    ];
                                                    ?>
                                                    <span class="badge status-<?php echo $order['status']; ?>">
                                                        <?php echo $status_text[$order['status']] ?? $order['status']; ?>
                                                    </span>
                                                </td>
                                                <td class="small text-muted">
                                                    <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                                </td>
                                                <td>
                                                    <a href="/restaurant-qrcode/admin/order-detail.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        <i data-lucide="eye" class="icon-sm"></i> ดูรายละเอียด
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="/restaurant-qrcode/admin/orders.php" class="btn btn-primary">
                                    <i data-lucide="list"></i>
                                    ดูออเดอร์ทั้งหมด
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

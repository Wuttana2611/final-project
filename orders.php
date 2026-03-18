<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get filter
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$query = "SELECT o.*, t.table_number,
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o
          JOIN tables t ON o.table_id = t.id
          WHERE 1=1";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_filter)) {
    $query .= " AND DATE(o.created_at) = :date";
    $params[':date'] = $date_filter;
}

$query .= " ORDER BY o.created_at DESC";

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$orders = $stmt->fetchAll();

$page_title = 'จัดการออเดอร์ - Admin';
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="shopping-cart"></i>
                    จัดการออเดอร์
                </h2>
                <p class="text-muted">ดูและจัดการออเดอร์ทั้งหมด</p>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">สถานะ</label>
                        <select name="status" class="form-select">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>ทั้งหมด</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>รอดำเนินการ</option>
                            <option value="preparing" <?php echo $status_filter === 'preparing' ? 'selected' : ''; ?>>กำลังปรุง</option>
                            <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>พร้อมเสิร์ฟ</option>
                            <option value="served" <?php echo $status_filter === 'served' ? 'selected' : ''; ?>>เสิร์ฟแล้ว</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>ยกเลิก</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">วันที่</label>
                        <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">
                                <i data-lucide="search"></i> ค้นหา
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Orders Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 text-muted">
                        <i data-lucide="inbox" style="width: 80px; height: 80px; opacity: 0.3;"></i>
                        <p class="mt-3">ไม่พบออเดอร์</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>โต๊ะ</th>
                                    <th>ชื่อลูกค้า</th>
                                    <th>จำนวนรายการ</th>
                                    <th>ยอดเงิน</th>
                                    <th>สถานะ</th>
                                    <th>วันที่</th>
                                    <th>จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong></td>
                                        <td>
                                            <i data-lucide="hash" class="icon-sm"></i>
                                            <?php echo htmlspecialchars($order['table_number']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($order['customer_name']) ?: '-'; ?></td>
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
                                        <td class="small">
                                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i data-lucide="eye" class="icon-sm"></i> ดู
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

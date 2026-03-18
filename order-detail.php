<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$order_id = $_GET['id'] ?? 0;

// Get order details
$query = "SELECT o.*, t.table_number 
          FROM orders o
          JOIN tables t ON o.table_id = t.id
          WHERE o.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $order_id);
$stmt->execute();
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Get order items
$query = "SELECT oi.*, mi.name, mi.image
          FROM order_items oi
          JOIN menu_items mi ON oi.menu_item_id = mi.id
          WHERE oi.order_id = :order_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':order_id', $order_id);
$stmt->execute();
$items = $stmt->fetchAll();

$page_title = 'รายละเอียดออเดอร์ #' . str_pad($order_id, 4, '0', STR_PAD_LEFT);
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="file-text"></i>
                    รายละเอียดออเดอร์ #<?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?>
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="orders.php">ออเดอร์</a></li>
                        <li class="breadcrumb-item active">รายละเอียด</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Info -->
            <div class="col-lg-4 mb-4">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="info"></i>
                            ข้อมูลออเดอร์
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="text-muted small">Order ID</label>
                            <p class="fw-bold">#<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">โต๊ะ</label>
                            <p class="fw-bold">
                                <i data-lucide="hash" class="icon-sm"></i>
                                <?php echo htmlspecialchars($order['table_number']); ?>
                            </p>
                        </div>
                        
                        <?php if (!empty($order['customer_name'])): ?>
                            <div class="mb-3">
                                <label class="text-muted small">ชื่อลูกค้า</label>
                                <p class="fw-bold">
                                    <i data-lucide="user" class="icon-sm"></i>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="text-muted small">สถานะ</label>
                            <p>
                                <?php
                                $status_text = [
                                    'pending' => 'รอดำเนินการ',
                                    'preparing' => 'กำลังปรุง',
                                    'ready' => 'พร้อมเสิร์ฟ',
                                    'served' => 'เสิร์ฟแล้ว',
                                    'cancelled' => 'ยกเลิก'
                                ];
                                ?>
                                <span class="badge status-<?php echo $order['status']; ?> fs-6">
                                    <?php echo $status_text[$order['status']] ?? $order['status']; ?>
                                </span>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">วันที่สั่ง</label>
                            <p class="fw-bold">
                                <i data-lucide="calendar" class="icon-sm"></i>
                                <?php echo date('d/m/Y H:i:s', strtotime($order['created_at'])); ?>
                            </p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small">อัปเดตล่าสุด</label>
                            <p class="fw-bold">
                                <i data-lucide="clock" class="icon-sm"></i>
                                <?php echo date('d/m/Y H:i:s', strtotime($order['updated_at'])); ?>
                            </p>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-0">
                            <label class="text-muted small">ยอดรวมทั้งหมด</label>
                            <h3 class="text-primary fw-bold mb-0">
                                ฿<?php echo number_format($order['total_amount'], 2); ?>
                            </h3>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="col-lg-8 mb-4">
                <div class="card fade-in" style="animation-delay: 0.1s;">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="list"></i>
                            รายการอาหาร
                            <span class="badge bg-secondary ms-2"><?php echo count($items); ?> รายการ</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th width="80">รูป</th>
                                        <th>เมนู</th>
                                        <th width="100" class="text-center">จำนวน</th>
                                        <th width="120" class="text-end">ราคา/หน่วย</th>
                                        <th width="150" class="text-end">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td>
                                                <?php if ($item['image']): ?>
                                                    <img src="/restaurant-qrcode/uploads/menu/<?php echo htmlspecialchars($item['image']); ?>" 
                                                         class="img-fluid rounded" style="width: 60px; height: 60px; object-fit: cover;"
                                                         onerror="this.src='/restaurant-qrcode/assets/images/no-image.jpg'">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 60px; height: 60px;">
                                                        <i data-lucide="image-off" class="text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?php echo $item['quantity']; ?></span>
                                            </td>
                                            <td class="text-end">
                                                ฿<?php echo number_format($item['price'], 2); ?>
                                            </td>
                                            <td class="text-end fw-bold text-primary">
                                                ฿<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <th colspan="4" class="text-end">ยอดรวมทั้งหมด:</th>
                                        <th class="text-end text-success">
                                            <h5 class="mb-0">฿<?php echo number_format($order['total_amount'], 2); ?></h5>
                                        </th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-center">
                    <a href="orders.php" class="btn btn-secondary">
                        <i data-lucide="arrow-left"></i>
                        กลับไปหน้าออเดอร์
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Get all tables
$query = "SELECT t.*, 
          (SELECT COUNT(*) FROM orders WHERE table_id = t.id AND status IN ('pending', 'preparing','served','ready')) as active_orders
          FROM tables t
          ORDER BY t.table_number";
$stmt = $db->query($query);
$tables = $stmt->fetchAll();

$page_title = 'จัดการโต๊ะ - Admin';
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold">
                    <i data-lucide="grid-2x2"></i>
                    จัดการโต๊ะ
                </h2>
                <p class="text-muted">ดูและจัดการ QR Code สำหรับโต๊ะแต่ละโต๊ะ</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($tables as $table): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="card h-100 fade-in">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <?php if ($table['status'] === 'occupied'): ?>
                                    <div class="stat-icon mx-auto" style="background-color: rgba(231, 76, 60, 0.1); width: 80px; height: 80px;">
                                        <i data-lucide="users" style="width: 40px; height: 40px; color: var(--danger-color);"></i>
                                    </div>
                                <?php else: ?>
                                    <div class="stat-icon mx-auto" style="background-color: rgba(39, 174, 96, 0.1); width: 80px; height: 80px;">
                                        <i data-lucide="check-circle" style="width: 40px; height: 40px; color: var(--success-color);"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <h4 class="fw-bold">โต๊ะ <?php echo htmlspecialchars($table['table_number']); ?></h4>
                            
                            <?php if ($table['status'] === 'occupied'): ?>
                                <span class="badge bg-danger mb-2">กำลังใช้งาน</span>
                            <?php else: ?>
                                <span class="badge bg-success mb-2">ว่าง</span>
                            <?php endif; ?>
                            
                            <?php if ($table['active_orders'] > 0): ?>
                                <p class="small text-muted mb-3">
                                    <i data-lucide="shopping-cart" class="icon-sm"></i>
                                    <?php echo $table['active_orders']; ?> ออเดอร์ที่กำลังดำเนินการ
                                </p>
                            <?php else: ?>
                                <p class="small text-muted mb-3">ไม่มีออเดอร์</p>
                            <?php endif; ?>
                            
                            <div class="d-grid gap-2">
                                <a href="table-qr.php?id=<?php echo $table['id']; ?>" class="btn btn-primary">
                                    <i data-lucide="qr-code"></i>
                                    ดู QR Code
                                </a>
                                
                                <?php if ($table['active_orders'] > 0): ?>
                                    <a href="checkout.php?table=<?php echo $table['id']; ?>" class="btn btn-success">
                                        <i data-lucide="receipt"></i>
                                        เช็คบิล
                                    </a>
                                <?php endif; ?>
                                
                                <a href="table-orders.php?id=<?php echo $table['id']; ?>" class="btn btn-outline-secondary">
                                    <i data-lucide="list"></i>
                                    ประวัติออเดอร์
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="info"></i>
                            สถิติโต๊ะ
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <h3 class="fw-bold text-primary"><?php echo count($tables); ?></h3>
                                <p class="text-muted mb-0">โต๊ะทั้งหมด</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="fw-bold text-danger">
                                    <?php echo count(array_filter($tables, fn($t) => $t['status'] === 'occupied')); ?>
                                </h3>
                                <p class="text-muted mb-0">กำลังใช้งาน</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="fw-bold text-success">
                                    <?php echo count(array_filter($tables, fn($t) => $t['status'] === 'available')); ?>
                                </h3>
                                <p class="text-muted mb-0">โต๊ะว่าง</p>
                            </div>
                            <div class="col-md-3">
                                <h3 class="fw-bold text-warning">
                                    <?php echo array_sum(array_column($tables, 'active_orders')); ?>
                                </h3>
                                <p class="text-muted mb-0">ออเดอร์รอดำเนินการ</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

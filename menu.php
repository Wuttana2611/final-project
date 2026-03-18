<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM menu_items WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        header('Location: menu.php?success=delete');
        exit;
    }
}

// Handle Toggle Availability
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $query = "UPDATE menu_items SET is_available = NOT is_available WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $id);
    if ($stmt->execute()) {
        header('Location: menu.php?success=toggle');
        exit;
    }
}

// Get all categories
$query = "SELECT * FROM categories ORDER BY display_order, name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll();

// Get all menu items with categories
$query = "SELECT mi.*, c.name as category_name 
          FROM menu_items mi
          JOIN categories c ON mi.category_id = c.id
          ORDER BY c.display_order, mi.name";
$stmt = $db->query($query);
$menu_items = $stmt->fetchAll();

$page_title = 'จัดการเมนู - Admin';
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2 class="fw-bold">
                    <i data-lucide="book-open"></i>
                    จัดการเมนูอาหาร
                </h2>
                <p class="text-muted">เพิ่ม แก้ไข และจัดการเมนูอาหารในระบบ</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="menu-add.php" class="btn btn-primary btn-lg">
                    <i data-lucide="plus"></i>
                    เพิ่มเมนูใหม่
                </a>
            </div>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i data-lucide="check-circle" class="icon-sm"></i>
                <?php
                $messages = [
                    'add' => 'เพิ่มเมนูสำเร็จ',
                    'edit' => 'แก้ไขเมนูสำเร็จ',
                    'delete' => 'ลบเมนูสำเร็จ',
                    'toggle' => 'เปลี่ยนสถานะสำเร็จ'
                ];
                echo $messages[$_GET['success']] ?? 'ดำเนินการสำเร็จ';
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($menu_items)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i data-lucide="inbox" style="width: 80px; height: 80px; opacity: 0.3; color: var(--primary-color);"></i>
                    <h4 class="mt-3 text-muted">ยังไม่มีเมนูในระบบ</h4>
                    <p class="text-muted">เริ่มต้นโดยการเพิ่มเมนูอาหารแรกของคุณ</p>
                    <a href="menu-add.php" class="btn btn-primary mt-3">
                        <i data-lucide="plus"></i>
                        เพิ่มเมนูแรก
                    </a>
                </div>
            </div>
        <?php else: ?>
            <?php
            // Group menu items by category
            $grouped_items = [];
            foreach ($menu_items as $item) {
                $grouped_items[$item['category_name']][] = $item;
            }
            ?>
            
            <?php foreach ($grouped_items as $category => $items): ?>
                <div class="card mb-4 fade-in">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="tag"></i>
                            <?php echo htmlspecialchars($category); ?>
                            <span class="badge bg-secondary ms-2"><?php echo count($items); ?> รายการ</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="card menu-item-card h-100">
                                        <div class="position-relative">
                                            <?php if ($item['image']): ?>
                                                <img src="/restaurant-qrcode/uploads/menu/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.src='/restaurant-qrcode/assets/images/no-image.jpg'">
                                            <?php else: ?>
                                                <div style="height: 200px; background: linear-gradient(135deg, #E67E22 0%, #D35400 100%); 
                                                            display: flex; align-items: center; justify-content: center;">
                                                    <i data-lucide="image-off" style="width: 60px; height: 60px; color: white; opacity: 0.5;"></i>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <?php if ($item['is_available']): ?>
                                                    <span class="badge bg-success">พร้อมขาย</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">ไม่พร้อมขาย</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="card-body">
                                            <h6 class="card-title fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="card-text small text-muted mb-2">
                                                <?php echo htmlspecialchars(substr($item['description'], 0, 50)) . (strlen($item['description']) > 50 ? '...' : ''); ?>
                                            </p>
                                            <p class="card-text mb-3">
                                                <span class="h5 text-primary fw-bold">฿<?php echo number_format($item['price'], 2); ?></span>
                                            </p>
                                            
                                            <div class="d-grid gap-2">
                                                <a href="menu-edit.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i data-lucide="edit" class="icon-sm"></i> แก้ไข
                                                </a>
                                                <a href="?toggle=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-warning">
                                                    <i data-lucide="toggle-left" class="icon-sm"></i>
                                                    <?php echo $item['is_available'] ? 'ปิดการขาย' : 'เปิดการขาย'; ?>
                                                </a>
                                                <a href="?delete=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('ยืนยันการลบเมนู <?php echo htmlspecialchars($item['name']); ?>?')">
                                                    <i data-lucide="trash-2" class="icon-sm"></i> ลบ
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

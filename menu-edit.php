<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$menu_id = $_GET['id'] ?? 0;

// Get menu item
$query = "SELECT * FROM menu_items WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $menu_id);
$stmt->execute();
$menu = $stmt->fetch();

if (!$menu) {
    header('Location: menu.php');
    exit;
}

// Get categories
$query = "SELECT * FROM categories ORDER BY display_order, name";
$stmt = $db->query($query);
$categories = $stmt->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $category_id = $_POST['category_id'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Validation
    if (empty($name)) {
        $errors[] = 'กรุณากรอกชื่อเมนู';
    }
    if (empty($category_id)) {
        $errors[] = 'กรุณาเลือกหมวดหมู่';
    }
    if (empty($price) || !is_numeric($price) || $price <= 0) {
        $errors[] = 'กรุณากรอกราคาที่ถูกต้อง';
    }
    
    // Handle image upload
    $image_name = $menu['image']; // Keep existing image
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = 'ไฟล์รูปภาพต้องเป็น JPG, JPEG หรือ PNG เท่านั้น';
        } else {
            $upload_dir = '../uploads/menu/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Delete old image if exists
            if ($menu['image'] && file_exists($upload_dir . $menu['image'])) {
                unlink($upload_dir . $menu['image']);
            }
            
            $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = uniqid('menu_') . '.' . $file_extension;
            $upload_path = $upload_dir . $image_name;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $errors[] = 'เกิดข้อผิดพลาดในการอัปโหลดรูปภาพ';
                $image_name = $menu['image']; // Revert to old image
            }
        }
    }
    
    // Update if no errors
    if (empty($errors)) {
        try {
            $query = "UPDATE menu_items 
                      SET category_id = :category_id, name = :name, description = :description, 
                          price = :price, image = :image, is_available = :is_available
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':image', $image_name);
            $stmt->bindParam(':is_available', $is_available);
            $stmt->bindParam(':id', $menu_id);
            
            if ($stmt->execute()) {
                header('Location: menu.php?success=edit');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

$page_title = 'แก้ไขเมนู - Admin';
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="edit"></i>
                    แก้ไขเมนู
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="menu.php">จัดการเมนู</a></li>
                        <li class="breadcrumb-item active">แก้ไข</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card fade-in">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i data-lucide="file-text"></i>
                            แก้ไขข้อมูลเมนู
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <i data-lucide="alert-circle" class="icon-sm"></i>
                                <strong>เกิดข้อผิดพลาด:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="name" class="form-label">
                                    <i data-lucide="type" class="icon-sm"></i> ชื่อเมนู *
                                </label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? $menu['name']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">
                                    <i data-lucide="tag" class="icon-sm"></i> หมวดหมู่ *
                                </label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- เลือกหมวดหมู่ --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"
                                                <?php echo (isset($_POST['category_id']) ? $_POST['category_id'] : $menu['category_id']) == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">
                                    <i data-lucide="align-left" class="icon-sm"></i> คำอธิบาย
                                </label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? $menu['description']); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price" class="form-label">
                                    <i data-lucide="dollar-sign" class="icon-sm"></i> ราคา (บาท) *
                                </label>
                                <input type="number" class="form-control" id="price" name="price" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? $menu['price']); ?>" 
                                       step="0.01" min="0" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="image" class="form-label">
                                    <i data-lucide="image" class="icon-sm"></i> รูปภาพ
                                </label>
                                
                                <?php if ($menu['image']): ?>
                                    <div class="mb-2">
                                        <img src="/restaurant-qrcode/uploads/menu/<?php echo htmlspecialchars($menu['image']); ?>" 
                                             class="img-fluid rounded" style="max-width: 200px;"
                                             onerror="this.style.display='none'">
                                        <p class="small text-muted mt-1">รูปภาพปัจจุบัน</p>
                                    </div>
                                <?php endif; ?>
                                
                                <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/jpg">
                                <div class="form-text">อัปโหลดรูปใหม่เพื่อเปลี่ยน (รองรับ JPG, JPEG, PNG)</div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="is_available" name="is_available" 
                                           <?php echo (isset($_POST['is_available']) ? isset($_POST['is_available']) : $menu['is_available']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_available">
                                        <i data-lucide="check-circle" class="icon-sm"></i> พร้อมขาย
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i data-lucide="save"></i>
                                    บันทึกการแก้ไข
                                </button>
                                <a href="menu.php" class="btn btn-secondary">
                                    <i data-lucide="x"></i>
                                    ยกเลิก
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once '../includes/footer.php'; ?>

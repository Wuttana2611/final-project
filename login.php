<?php
require_once '../config/session.php';
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $query = "SELECT id, username, password, full_name, role FROM users WHERE username = :username LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                // Verify password (for demo, using password_verify - default password is 'password')
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header('Location: /restaurant-qrcode/admin/dashboard.php');
                            break;
                        case 'chef':
                            header('Location: /restaurant-qrcode/chef/kitchen.php');
                            break;
                        default:
                            header('Location: /restaurant-qrcode/index.php');
                    }
                    exit;
                } else {
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            } else {
                $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
            }
        } catch (PDOException $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

$page_title = 'เข้าสู่ระบบ';
$hide_nav = true;
$hide_footer = true;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Restaurant QR</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/restaurant-qrcode/assets/css/style.css">
</head>
<body>

<div class="login-container">
    <div class="login-card card shadow-lg fade-in">
        <div class="card-body">
            <div class="text-center mb-4">
                <div class="mb-3">
                    <i data-lucide="utensils" style="width: 60px; height: 60px; color: var(--primary-color);"></i>
                </div>
                <h2 class="fw-bold text-primary">Restaurant QR</h2>
                <p class="text-muted">เข้าสู่ระบบจัดการร้านอาหาร</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i data-lucide="alert-circle" class="icon-sm"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i data-lucide="user" class="icon-sm"></i> ชื่อผู้ใช้
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="กรอกชื่อผู้ใช้" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i data-lucide="lock" class="icon-sm"></i> รหัสผ่าน
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="กรอกรหัสผ่าน" required>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 btn-lg">
                    <i data-lucide="log-in" class="icon-sm"></i>
                    เข้าสู่ระบบ
                </button>
            </form>
            
            <div class="mt-4 p-3 bg-light rounded">
                <p class="mb-2 fw-600"><i data-lucide="info" class="icon-sm"></i> บัญชีทดสอบ:</p>
                <div class="small">
                    <p class="mb-1"><strong>Admin:</strong> username: <code>admin</code> / password: <code>password</code></p>
                    <p class="mb-0"><strong>Chef:</strong> username: <code>chef</code> / password: <code>password</code></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    lucide.createIcons();
</script>

</body>
</html>

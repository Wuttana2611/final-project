<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Restaurant QR Ordering System'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Prompt -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/restaurant-qrcode/assets/css/style.css">
    
    <!-- Suppress browser extension errors -->
    <script>
        window.addEventListener('error', function(e) {
            if (e.filename && (e.filename.includes('pagehelper.js') || e.filename.includes('chrome-extension://') || e.filename.includes('moz-extension://'))) {
                e.preventDefault();
                return true;
            }
        });
    </script>
    
    <?php if (isset($extra_css)): ?>
        <?php foreach ($extra_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <?php if (!isset($hide_nav) || !$hide_nav): ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="/restaurant-qrcode/index.php">
                    <i data-lucide="utensils" class="me-2"></i>
                    Restaurant QR
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/restaurant-qrcode/admin/dashboard.php">
                                        <i data-lucide="layout-dashboard" class="icon-sm"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/restaurant-qrcode/admin/menu.php">
                                        <i data-lucide="book-open" class="icon-sm"></i> จัดการเมนู
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/restaurant-qrcode/admin/tables.php">
                                        <i data-lucide="grid-2x2" class="icon-sm"></i> จัดการโต๊ะ
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="/restaurant-qrcode/admin/orders.php">
                                        <i data-lucide="shopping-cart" class="icon-sm"></i> ออเดอร์
                                    </a>
                                </li>
                            <?php elseif ($_SESSION['role'] === 'chef'): ?>
                                <li class="nav-item">
                                    <a class="nav-link" href="/restaurant-qrcode/chef/kitchen.php">
                                        <i data-lucide="chef-hat" class="icon-sm"></i> Kitchen Display
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                    <i data-lucide="user" class="icon-sm"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="/restaurant-qrcode/auth/logout.php">
                                        <i data-lucide="log-out" class="icon-sm"></i> ออกจากระบบ
                                    </a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="/restaurant-qrcode/auth/login.php">
                                    <i data-lucide="log-in" class="icon-sm"></i> เข้าสู่ระบบ
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    <?php endif; ?>

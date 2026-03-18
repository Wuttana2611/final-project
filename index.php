<?php
require_once 'config/session.php';

// Redirect logged in users to their dashboard
if (isLoggedIn()) {
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /restaurant-qrcode/admin/dashboard.php');
            break;
        case 'chef':
            header('Location: /restaurant-qrcode/chef/kitchen.php');
            break;
    }
    exit;
}

$page_title = 'หน้าหลัก';
require_once 'includes/header.php';
?>

<main>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-5 fade-in">
                    <h1 class="display-3 fw-bold text-primary mb-3">
                        <i data-lucide="utensils" style="width: 80px; height: 80px;"></i>
                        <br>Restaurant QR Ordering
                    </h1>
                    <p class="lead text-muted">ระบบสั่งอาหารผ่าน QR Code ที่ทันสมัยและใช้งานง่าย</p>
                </div>
                
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card h-100 text-center fade-in" style="animation-delay: 0.1s;">
                            <div class="card-body p-5">
                                <div class="mb-4">
                                    <i data-lucide="smartphone" style="width: 80px; height: 80px; color: var(--primary-color);"></i>
                                </div>
                                <h3 class="fw-bold mb-3">สแกน QR Code</h3>
                                <p class="text-muted mb-4">
                                    ลูกค้าสแกน QR Code ที่โต๊ะเพื่อเข้าสู่หน้าเมนูอาหาร
                                    และสั่งอาหารได้ทันที ไม่ต้องรอพนักงาน
                                </p>
                                <a href="/restaurant-qrcode/customer/order.php?table=demo&token=demo123" 
                                   class="btn btn-primary btn-lg">
                                    <i data-lucide="qr-code"></i>
                                    ทดลองสั่งอาหาร (Demo)
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card h-100 text-center fade-in" style="animation-delay: 0.2s;">
                            <div class="card-body p-5">
                                <div class="mb-4">
                                    <i data-lucide="shield-check" style="width: 80px; height: 80px; color: var(--success-color);"></i>
                                </div>
                                <h3 class="fw-bold mb-3">เข้าสู่ระบบจัดการ</h3>
                                <p class="text-muted mb-4">
                                    สำหรับผู้ดูแลระบบและพนักงาน 
                                    เข้าสู่ระบบเพื่อจัดการเมนู, ออเดอร์, และครัว
                                </p>
                                <a href="/restaurant-qrcode/auth/login.php" class="btn btn-success btn-lg">
                                    <i data-lucide="log-in"></i>
                                    เข้าสู่ระบบ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-5 g-4">
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-0 bg-light fade-in" style="animation-delay: 0.3s;">
                            <div class="card-body p-4">
                                <i data-lucide="clock" style="width: 50px; height: 50px; color: var(--primary-color);"></i>
                                <h5 class="mt-3 fw-bold">รวดเร็ว</h5>
                                <p class="text-muted small mb-0">ลูกค้าสั่งอาหารได้ทันที ลดเวลารอคอย</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-0 bg-light fade-in" style="animation-delay: 0.4s;">
                            <div class="card-body p-4">
                                <i data-lucide="check-circle" style="width: 50px; height: 50px; color: var(--success-color);"></i>
                                <h5 class="mt-3 fw-bold">แม่นยำ</h5>
                                <p class="text-muted small mb-0">ลดข้อผิดพลาดจากการจดออเดอร์</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card text-center h-100 border-0 bg-light fade-in" style="animation-delay: 0.5s;">
                            <div class="card-body p-4">
                                <i data-lucide="trending-up" style="width: 50px; height: 50px; color: var(--warning-color);"></i>
                                <h5 class="mt-3 fw-bold">เพิ่มยอดขาย</h5>
                                <p class="text-muted small mb-0">ระบบที่ทันสมัย เพิ่มประสบการณ์ลูกค้า</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>

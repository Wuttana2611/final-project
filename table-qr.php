<?php
require_once '../config/session.php';
require_once '../config/database.php';

requireRole('admin');

$database = new Database();
$db = $database->getConnection();

$table_id = $_GET['id'] ?? 0;

// Get table information
$query = "SELECT * FROM tables WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $table_id);
$stmt->execute();
$table = $stmt->fetch();

if (!$table) {
    header('Location: tables.php');
    exit;
}

// Generate session token for QR code
if (empty($table['session_token'])) {
    $token = bin2hex(random_bytes(16));
    $query = "UPDATE tables SET session_token = :token WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':id', $table_id);
    $stmt->execute();
    $table['session_token'] = $token;
}

// Generate QR Code URL
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$order_url = $base_url . "/restaurant-qrcode/customer/order.php?table=" . urlencode($table['table_number']) . "&token=" . urlencode($table['session_token']);

$page_title = 'QR Code - โต๊ะ ' . $table['table_number'];
require_once '../includes/header.php';
?>

<main class="py-4">
    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">
                    <i data-lucide="qr-code"></i>
                    QR Code - โต๊ะ <?php echo htmlspecialchars($table['table_number']); ?>
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="tables.php">จัดการโต๊ะ</a></li>
                        <li class="breadcrumb-item active">QR Code</li>
                    </ol>
                </nav>
            </div>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card fade-in">
                    <div class="card-body p-5">
                        <div class="qr-container">
                            <h3 class="fw-bold text-primary mb-4">
                                <i data-lucide="hash"></i>
                                โต๊ะ <?php echo htmlspecialchars($table['table_number']); ?>
                            </h3>
                            
                            <!-- QR Code (Using QRCode.js) -->
                            <div class="mb-4 d-flex justify-content-center">
                                <div id="qrcode"></div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i data-lucide="info" class="icon-sm"></i>
                                <strong>วิธีใช้งาน:</strong><br>
                                ลูกค้าสแกน QR Code นี้ด้วยกล้องมือถือเพื่อเข้าสู่หน้าสั่งอาหาร
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-600">
                                    <i data-lucide="link" class="icon-sm"></i> URL สำหรับสั่งอาหาร:
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="orderUrl" value="<?php echo htmlspecialchars($order_url); ?>" readonly>
                                    <button class="btn btn-outline-primary" type="button" onclick="copyUrl()">
                                        <i data-lucide="copy"></i> คัดลอก
                                    </button>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-2 justify-content-center">
                                <button onclick="printQR()" class="btn btn-primary">
                                    <i data-lucide="printer"></i>
                                    พิมพ์ QR Code
                                </button>
                                
                                <a href="<?php echo $order_url; ?>" target="_blank" class="btn btn-success">
                                    <i data-lucide="external-link"></i>
                                    ทดสอบสั่งอาหาร
                                </a>
                                
                                <a href="tables.php" class="btn btn-secondary">
                                    <i data-lucide="arrow-left"></i>
                                    กลับ
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Printable QR Code -->
                <div id="printable" style="display: none;">
                    <div style="text-align: center; padding: 40px;">
                        <h1 style="font-family: 'Prompt', sans-serif; color: #E67E22; margin-bottom: 20px;">
                            Restaurant QR Ordering
                        </h1>
                        <h2 style="font-family: 'Prompt', sans-serif; margin-bottom: 30px;">
                            โต๊ะ <?php echo htmlspecialchars($table['table_number']); ?>
                        </h2>
                        <div id="qrcode-print" style="display: inline-block;"></div>
                        <p style="margin-top: 30px; font-family: 'Prompt', sans-serif; font-size: 18px;">
                            สแกน QR Code เพื่อสั่งอาหาร
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- QRCode.js Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<script>
// Generate QR Code
const orderUrl = '<?php echo $order_url; ?>';

// QR Code for display
new QRCode(document.getElementById("qrcode"), {
    text: orderUrl,
    width: 300,
    height: 300,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

// QR Code for print
new QRCode(document.getElementById("qrcode-print"), {
    text: orderUrl,
    width: 400,
    height: 400,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});

function copyUrl() {
    const urlInput = document.getElementById('orderUrl');
    urlInput.select();
    urlInput.setSelectionRange(0, 99999);
    
    navigator.clipboard.writeText(urlInput.value).then(function() {
        showToast('คัดลอก URL สำเร็จ', 'success');
    }, function(err) {
        alert('ไม่สามารถคัดลอกได้: ' + err);
    });
}

function printQR() {
    const printContent = document.getElementById('printable').innerHTML;
    const originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}
</script>

<?php require_once '../includes/footer.php'; ?>

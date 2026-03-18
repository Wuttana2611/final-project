<?php
require_once '../config/database.php';

session_start();

// Get table and token from URL
$table_number = $_GET['table'] ?? '';
$session_token = $_GET['token'] ?? '';

if (empty($table_number) || empty($session_token)) {
    die('Invalid access. Please scan QR code from your table.');
}

$database = new Database();
$db = $database->getConnection();

// Verify table and token
$query = "SELECT * FROM tables WHERE table_number = :table_number AND session_token = :token";
$stmt = $db->prepare($query);
$stmt->bindParam(':table_number', $table_number);
$stmt->bindParam(':token', $session_token);
$stmt->execute();
$table = $stmt->fetch();

if (!$table) {
    die('Invalid table or token.');
}

// Get orders for this table with current session token only
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o
          WHERE o.table_id = :table_id 
          AND o.session_token = :session_token
          AND o.status IN ('pending', 'preparing', 'ready', 'served')
          ORDER BY o.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':table_id', $table['id']);
$stmt->bindParam(':session_token', $session_token);
$stmt->execute();
$orders = $stmt->fetchAll();

// Calculate total
$total_amount = array_sum(array_column($orders, 'total_amount'));

$page_title = 'เช็คบิล - โต๊ะ ' . $table_number;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/restaurant-qrcode/assets/css/style.css">
    
    <style>
        .cancel-btn {
            padding: 2px 8px;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        .cancelled-item {
            opacity: 0.6;
            text-decoration: line-through;
        }
        .cancel-quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        .cancel-quantity-control button {
            width: 25px;
            height: 25px;
            padding: 0;
            font-size: 0.8rem;
        }
        .cancel-quantity-control input {
            width: 50px;
            text-align: center;
            padding: 2px 5px;
        }
    </style>
    
    <script>
        // Suppress browser extension errors
        window.addEventListener('error', function(e) {
            if (e.filename && (e.filename.includes('pagehelper.js') || e.filename.includes('chrome-extension://') || e.filename.includes('moz-extension://'))) {
                e.preventDefault();
                return true;
            }
        });
    </script>
    
    <style>
        .cancel-btn {
            padding: 2px 8px;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        .cancelled-item {
            opacity: 0.6;
            text-decoration: line-through;
        }
        .cancel-quantity-control {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }
        .cancel-quantity-control button {
            width: 25px;
            height: 25px;
            padding: 0;
            font-size: 0.8rem;
        }
        .cancel-quantity-control input {
            width: 50px;
            text-align: center;
            padding: 2px 5px;
        }
    </style>
</head>
<body>

<div class="container py-4">
    <!-- Header -->
    <div class="text-center mb-4 fade-in">
        <h1 class="display-6 fw-bold text-primary">
            <i data-lucide="receipt" style="width: 40px; height: 40px;"></i>
            เช็คบิล
        </h1>
        <p class="lead">
            <i data-lucide="hash" class="icon-sm"></i>
            โต๊ะ <?php echo htmlspecialchars($table_number); ?>
        </p>
    </div>
    
    <?php if (empty($orders)): ?>
        <div class="card fade-in">
            <div class="card-body text-center py-5">
                <i data-lucide="inbox" style="width: 80px; height: 80px; opacity: 0.3; color: var(--primary-color);"></i>
                <h4 class="mt-3 text-muted">ยังไม่มีออเดอร์</h4>
                <p class="text-muted">คุณยังไม่ได้สั่งอาหาร</p>
                <a href="order.php?table=<?php echo urlencode($table_number); ?>&token=<?php echo urlencode($session_token); ?>" 
                   class="btn btn-primary mt-3">
                    <i data-lucide="utensils"></i>
                    สั่งอาหาร
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Orders List -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <?php foreach ($orders as $order): ?>
                    <?php
                    // Get order items
                    $query = "SELECT oi.*, mi.name
                              FROM order_items oi
                              JOIN menu_items mi ON oi.menu_item_id = mi.id
                              WHERE oi.order_id = :order_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':order_id', $order['id']);
                    $stmt->execute();
                    $items = $stmt->fetchAll();
                    ?>
                    
                    <div class="card mb-3 fade-in">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Order #<?php echo str_pad($order['id'], 4, '0', STR_PAD_LEFT); ?></strong>
                                    <span class="text-muted ms-2 small">
                                        <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <?php
                                $status_text = [
                                    'pending' => 'รอดำเนินการ',
                                    'preparing' => 'กำลังปรุง',
                                    'ready' => 'พร้อมเสิร์ฟ',
                                    'served' => 'เสิร์ฟแล้ว'
                                ];
                                ?>
                                <span class="badge status-<?php echo $order['status']; ?>">
                                    <?php echo $status_text[$order['status']] ?? $order['status']; ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <?php 
                                                $cancelled_qty = $item['cancelled_quantity'] ?? 0;
                                                $active_qty = $item['quantity'] - $cancelled_qty;
                                                $can_cancel_flag = $item['can_cancel'] ?? true;
                                                $can_cancel = $order['status'] === 'pending' && $active_qty > 0 && $can_cancel_flag;
                                            ?>
                                            <tr <?php echo $cancelled_qty > 0 ? 'class="cancelled-item"' : ''; ?>>
                                                <td>
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                    <?php if ($cancelled_qty > 0): ?>
                                                        <small class="text-danger d-block">ยกเลิก <?php echo $cancelled_qty; ?> รายการ</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center" width="60">
                                                    <?php if ($cancelled_qty > 0): ?>
                                                        <span class="badge bg-secondary"><?php echo $active_qty; ?></span>
                                                        <small class="text-muted d-block">จาก <?php echo $item['quantity']; ?></small>
                                                    <?php else: ?>
                                                        <span class="badge bg-primary"><?php echo $item['quantity']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end" width="100">
                                                    ฿<?php echo number_format($item['price'], 2); ?>
                                                </td>
                                                <td class="text-end fw-bold" width="100">
                                                    ฿<?php echo number_format($item['price'] * $active_qty, 2); ?>
                                                </td>
                                                <td class="text-center" width="80">
                                                    <?php if ($can_cancel): ?>
                                                        <button class="btn btn-outline-danger btn-sm cancel-btn" 
                                                                onclick="showCancelModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $active_qty; ?>)">
                                                            <i data-lucide="x" style="width: 12px; height: 12px;"></i>
                                                        </button>
                                                    <?php elseif ($order['status'] !== 'pending'): ?>
                                                        <small class="text-muted">ไม่สามารถยกเลิก</small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-active">
                                            <td colspan="3" class="text-end"><strong>รวม:</strong></td>
                                            <td class="text-end fw-bold text-primary">
                                                ฿<?php echo number_format($order['total_amount'], 2); ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Total Summary -->
                <div class="card fade-in" style="animation-delay: 0.2s;">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-6">
                                <h5 class="mb-0">ยอดรวมทั้งหมด</h5>
                                <p class="text-muted small mb-0">
                                    <?php echo count($orders); ?> ออเดอร์ / 
                                    <?php echo array_sum(array_column($orders, 'item_count')); ?> รายการ
                                </p>
                            </div>
                            <div class="col-6 text-end">
                                <h2 class="text-success fw-bold mb-0">
                                    ฿<?php echo number_format($total_amount, 2); ?>
                                </h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="text-center mt-4">
                    <a href="order.php?table=<?php echo urlencode($table_number); ?>&token=<?php echo urlencode($session_token); ?>" 
                       class="btn btn-primary btn-lg">
                        <i data-lucide="plus"></i>
                        สั่งเพิ่ม
                    </a>
                    
                    <button class="btn btn-outline-secondary btn-lg ms-2" onclick="window.print()">
                        <i data-lucide="printer"></i>
                        พิมพ์บิล
                    </button>
                </div>
                
                <div class="alert alert-info mt-4 text-center">
                    <i data-lucide="info" class="icon-sm"></i>
                    <strong>หมายเหตุ:</strong> กรุณาชำระเงินที่เคาน์เตอร์หน้าร้าน<br>
                    ขอบคุณที่ใช้บริการครับ 🙏
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Cancel Item Modal -->
<div class="modal fade" id="cancelModal" tabindex="-1" aria-labelledby="cancelModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="cancelModalLabel">
                    <i data-lucide="x-circle"></i>
                    ยกเลิกรายการอาหาร
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="cancel-item-name" class="fw-bold mb-3"></p>
                <div class="cancel-quantity-control">
                    <label for="cancel-quantity" class="form-label">จำนวนที่ต้องการยกเลิก:</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary" onclick="adjustCancelQuantity(-1)">-</button>
                        <input type="number" class="form-control" id="cancel-quantity" value="1" min="1" max="1">
                        <button type="button" class="btn btn-outline-secondary" onclick="adjustCancelQuantity(1)">+</button>
                        <span class="text-muted">/ <span id="max-cancel-quantity">1</span> รายการ</span>
                    </div>
                </div>
                <div class="alert alert-warning mt-3">
                    <i data-lucide="alert-triangle" style="width: 16px; height: 16px;"></i>
                    <strong>คำเตือน:</strong> เมื่อยกเลิกแล้วจะไม่สามารถย้อนกลับได้
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                <button type="button" class="btn btn-danger" onclick="confirmCancel()">
                    <i data-lucide="trash-2"></i>
                    ยืนยันยกเลิก
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/restaurant-qrcode/assets/js/main.js"></script>
<script>
// Cancel functionality
let currentCancelItemId = null;
let currentMaxQuantity = 0;

function showCancelModal(itemId, itemName, maxQuantity) {
    currentCancelItemId = itemId;
    currentMaxQuantity = maxQuantity;
    
    document.getElementById('cancel-item-name').textContent = itemName;
    document.getElementById('max-cancel-quantity').textContent = maxQuantity;
    document.getElementById('cancel-quantity').setAttribute('max', maxQuantity);
    document.getElementById('cancel-quantity').value = 1;
    
    const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
    modal.show();
}

function adjustCancelQuantity(delta) {
    const input = document.getElementById('cancel-quantity');
    const current = parseInt(input.value);
    const newValue = Math.max(1, Math.min(currentMaxQuantity, current + delta));
    input.value = newValue;
}

async function confirmCancel() {
    if (!currentCancelItemId) return;
    
    const cancelQuantity = parseInt(document.getElementById('cancel-quantity').value);
    const sessionToken = '<?php echo htmlspecialchars($session_token, ENT_QUOTES); ?>';
    
    try {
        const response = await fetch('/restaurant-qrcode/api/cancel-item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                order_item_id: currentCancelItemId,
                cancel_quantity: cancelQuantity,
                session_token: sessionToken
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('cancelModal'));
            modal.hide();
            
            // Show success message
            showAlert('success', result.message);
            
            // Refresh page after 2 seconds
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        } else {
            showAlert('danger', result.message || 'เกิดข้อผิดพลาดในการยกเลิก');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('danger', 'เกิดข้อผิดพลาดในการเชื่อมต่อ');
    }
}

function showAlert(type, message) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Add to body
    document.body.appendChild(alertDiv);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.parentNode.removeChild(alertDiv);
        }
    }, 5000);
}

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>

</body>
</html>

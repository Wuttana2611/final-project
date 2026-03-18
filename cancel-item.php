<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$order_item_id = $input['order_item_id'] ?? 0;
$cancel_quantity = $input['cancel_quantity'] ?? 0;
$session_token = $input['session_token'] ?? '';

// Validate input
if (empty($order_item_id) || empty($cancel_quantity) || empty($session_token)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

if ($cancel_quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'จำนวนที่ยกเลิกต้องมากกว่า 0']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get order item details and verify ownership
    $query = "SELECT oi.*, o.status as order_status, o.session_token
              FROM order_items oi
              JOIN orders o ON oi.order_id = o.id
              WHERE oi.id = :order_item_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_item_id', $order_item_id);
    $stmt->execute();
    
    $order_item = $stmt->fetch();
    
    if (!$order_item) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรายการอาหารที่ระบุ']);
        exit;
    }
    
    // Verify session token
    if ($order_item['session_token'] !== $session_token) {
        echo json_encode(['success' => false, 'message' => 'ไม่มีสิทธิ์เข้าถึงออเดอร์นี้']);
        exit;
    }
    
    // Check can_cancel flag
    if (isset($order_item['can_cancel']) && !$order_item['can_cancel']) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถยกเลิกได้ พ่อครัวเริ่มปรุงแล้ว']);
        exit;
    }
    
    // Check if order is still pending (can only cancel if not started cooking)
    if ($order_item['order_status'] !== 'pending') {
        $status_text = [
            'preparing' => 'เริ่มปรุงแล้ว',
            'ready' => 'เสร็จแล้ว', 
            'served' => 'เสิร์ฟแล้ว',
            'completed' => 'เสร็จสิ้น',
            'cancelled' => 'ยกเลิกแล้ว'
        ];
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่สามารถยกเลิกได้ เนื่องจากออเดอร์' . ($status_text[$order_item['order_status']] ?? $order_item['order_status'])
        ]);
        exit;
    }
    
    // Check if there's enough quantity to cancel
    $available_quantity = $order_item['quantity'] - $order_item['cancelled_quantity'];
    if ($cancel_quantity > $available_quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'ไม่สามารถยกเลิกได้ มีรายการเหลือเพียง ' . $available_quantity . ' รายการ'
        ]);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update cancelled quantity
    $new_cancelled_quantity = $order_item['cancelled_quantity'] + $cancel_quantity;
    $query = "UPDATE order_items 
              SET cancelled_quantity = :cancelled_quantity, 
                  cancelled_at = NOW()
              WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':cancelled_quantity', $new_cancelled_quantity);
    $stmt->bindParam(':id', $order_item_id);
    $stmt->execute();
    
    // Calculate new order total
    $query = "SELECT SUM((quantity - cancelled_quantity) * price) as new_total
              FROM order_items 
              WHERE order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_item['order_id']);
    $stmt->execute();
    $result = $stmt->fetch();
    $new_total = $result['new_total'] ?? 0;
    
    // Update order total
    $query = "UPDATE orders SET total_amount = :total_amount WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':total_amount', $new_total);
    $stmt->bindParam(':id', $order_item['order_id']);
    $stmt->execute();
    
    // Check if all items in order are cancelled
    $query = "SELECT COUNT(*) as total_items,
                     SUM(CASE WHEN quantity = cancelled_quantity THEN 1 ELSE 0 END) as cancelled_items
              FROM order_items 
              WHERE order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_item['order_id']);
    $stmt->execute();
    $counts = $stmt->fetch();
    
    // If all items are cancelled, mark order as cancelled
    if ($counts['total_items'] == $counts['cancelled_items']) {
        $query = "UPDATE orders SET status = 'cancelled' WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $order_item['order_id']);
        $stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'ยกเลิกรายการสำเร็จ ' . $cancel_quantity . ' รายการ',
        'new_total' => number_format($new_total, 2),
        'cancelled_quantity' => $cancel_quantity,
        'remaining_quantity' => $order_item['quantity'] - $new_cancelled_quantity
    ]);
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}
?>
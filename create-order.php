<?php
header('Content-Type: application/json');

require_once '../config/database.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$table_id = $input['table_id'] ?? 0;
$session_token = $input['session_token'] ?? '';
$customer_name = $input['customer_name'] ?? '';
$items = $input['items'] ?? [];

// Validate
if (empty($table_id) || empty($session_token) || empty($items)) {
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Verify table and token
    $query = "SELECT * FROM tables WHERE id = :id AND session_token = :token";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $table_id);
    $stmt->bindParam(':token', $session_token);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'โต๊ะหรือ token ไม่ถูกต้อง']);
        exit;
    }
    
    // Calculate total
    $total_amount = 0;
    foreach ($items as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Insert order
    $query = "INSERT INTO orders (table_id, session_token, customer_name, total_amount, status) 
              VALUES (:table_id, :session_token, :customer_name, :total_amount, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':table_id', $table_id);
    $stmt->bindParam(':session_token', $session_token);
    $stmt->bindParam(':customer_name', $customer_name);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->execute();
    
    $order_id = $db->lastInsertId();
    
    // Insert order items
    $query = "INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
              VALUES (:order_id, :menu_item_id, :quantity, :price)";
    $stmt = $db->prepare($query);
    
    foreach ($items as $item) {
        $stmt->bindParam(':order_id', $order_id);
        $stmt->bindParam(':menu_item_id', $item['id']);
        $stmt->bindParam(':quantity', $item['quantity']);
        $stmt->bindParam(':price', $item['price']);
        $stmt->execute();
    }
    
    // Update table status
    $query = "UPDATE tables SET status = 'occupied' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $table_id);
    $stmt->execute();
    
    // Commit transaction
    $db->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'สั่งอาหารสำเร็จ',
        'order_id' => $order_id
    ]);
    
} catch (PDOException $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}

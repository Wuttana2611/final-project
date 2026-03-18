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

// Store in session
$_SESSION['customer_table_id'] = $table['id'];
$_SESSION['customer_table_number'] = $table['table_number'];
$_SESSION['customer_session_token'] = $session_token;

// Get all menu items grouped by category
$query = "SELECT c.id as category_id, c.name as category_name, c.display_order,
          mi.id, mi.name, mi.description, mi.price, mi.image
          FROM categories c
          LEFT JOIN menu_items mi ON c.id = mi.category_id AND mi.is_available = 1
          ORDER BY c.display_order, mi.name";
$stmt = $db->query($query);
$all_items = $stmt->fetchAll();

// Group by category
$menu = [];
foreach ($all_items as $item) {
    if ($item['id']) { // Only add if menu item exists
        $menu[$item['category_name']][] = $item;
    }
}

$page_title = 'สั่งอาหาร - โต๊ะ ' . $table_number;
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <title><?php echo $page_title; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/restaurant-qrcode/assets/css/style.css">
    
    <script>
        // Suppress browser extension errors (pagehelper.js, etc.)
        window.addEventListener('error', function(e) {
            if (e.filename && (e.filename.includes('pagehelper.js') || e.filename.includes('chrome-extension://') || e.filename.includes('moz-extension://'))) {
                e.preventDefault();
                return true;
            }
        });
    </script>
    
    <style>
        .cart-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .cart-badge .btn-lg {
            width: 60px;
            height: 60px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50% !important;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .quantity-control button {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .cart-item {
            border-bottom: 1px solid #eee;
            padding: 15px 0;
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .cart-badge {
                top: 15px;
                right: 15px;
            }
            
            .cart-badge .btn-lg {
                width: 56px;
                height: 56px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .display-5 {
                font-size: 1.8rem !important;
            }
            
            .lead {
                font-size: 1rem !important;
            }
            
            .menu-item-card .card-body {
                padding: 1rem;
            }
            
            .menu-item-card .card-title {
                font-size: 1rem;
            }
            
            .cart-sidebar {
                padding: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            .cart-badge .btn-lg {
                width: 52px;
                height: 52px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .cart-badge .btn-lg i {
                width: 22px !important;
                height: 22px !important;
            }
            
            .col-md-6 {
                padding-left: 8px;
                padding-right: 8px;
            }
            
            .g-4 {
                --bs-gutter-x: 1rem;
            }
        }
    </style>
</head>
<body>

<!-- Floating Cart Badge -->
<div class="cart-badge">
    <button class="btn btn-primary btn-lg rounded-circle position-relative" onclick="toggleCart()" id="cartButton">
        <i data-lucide="shopping-cart" style="width: 24px; height: 24px;"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cartCount">
            0
        </span>
    </button>
    <a href="bill.php?table=<?php echo urlencode($table_number); ?>&token=<?php echo urlencode($session_token); ?>" 
       class="btn btn-success btn-lg rounded-circle position-relative mt-2" 
       data-bs-toggle="tooltip" title="เช็คบิล">
        <i data-lucide="receipt" style="width: 24px; height: 24px;"></i>
    </a>
</div>

<!-- Cart Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">
                <i data-lucide="shopping-cart"></i>
                ตะกร้าสินค้า
            </h4>
            <button class="btn btn-sm btn-outline-secondary" onclick="toggleCart()">
                <i data-lucide="x"></i>
            </button>
        </div>
        
        <div id="cartItems">
            <div class="text-center py-5 text-muted">
                <i data-lucide="shopping-cart" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                <p class="mt-3">ตะกร้าว่าง</p>
            </div>
        </div>
        
        <div id="cartFooter" style="display: none;">
            <div class="border-top pt-3 mt-3">
                <div class="d-flex justify-content-between mb-3">
                    <h5>ยอดรวม:</h5>
                    <h5 class="text-primary fw-bold" id="cartTotal">฿0.00</h5>
                </div>
                
                <div class="mb-3">
                    <label for="customerName" class="form-label">ชื่อผู้สั่ง (ไม่บังคับ)</label>
                    <input type="text" class="form-control" id="customerName" placeholder="กรอกชื่อของคุณ">
                </div>
                
                <button class="btn btn-success w-100 btn-lg" onclick="confirmOrder()">
                    <i data-lucide="check-circle"></i>
                    ยืนยันการสั่ง
                </button>
                
                <button class="btn btn-outline-danger w-100 mt-2" onclick="clearCart()">
                    <i data-lucide="trash-2"></i>
                    ล้างตะกร้า
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container py-4">
    <!-- Header -->
    <div class="text-center mb-5 fade-in">
        <h1 class="display-5 fw-bold text-primary">
            <i data-lucide="utensils" style="width: 50px; height: 50px;"></i>
            เมนูอาหาร
        </h1>
        <p class="lead">
            <i data-lucide="hash" class="icon-sm"></i>
            โต๊ะ <?php echo htmlspecialchars($table_number); ?>
        </p>
    </div>
    
    <!-- Menu Items -->
    <?php foreach ($menu as $category_name => $items): ?>
        <div class="mb-5 fade-in">
            <h3 class="fw-bold mb-4 pb-2 border-bottom border-primary border-3">
                <i data-lucide="tag"></i>
                <?php echo htmlspecialchars($category_name); ?>
            </h3>
            
            <div class="row g-4">
                <?php foreach ($items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card menu-item-card h-100">
                            <?php if ($item['image']): ?>
                                <img src="/restaurant-qrcode/uploads/menu/<?php echo htmlspecialchars($item['image']); ?>" 
                                     class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     onerror="this.src='/restaurant-qrcode/assets/images/no-image.jpg'">
                            <?php else: ?>
                                <div style="height: 200px; background: linear-gradient(135deg, #E67E22 0%, #D35400 100%); 
                                            display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="utensils" style="width: 60px; height: 60px; color: white; opacity: 0.5;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <div class="menu-item-badge">฿<?php echo number_format($item['price'], 0); ?></div>
                            
                            <div class="card-body">
                                <h5 class="card-title fw-bold"><?php echo htmlspecialchars($item['name']); ?></h5>
                                <p class="card-text text-muted small mb-3">
                                    <?php echo htmlspecialchars($item['description']); ?>
                                </p>
                                
                                <button class="btn btn-primary w-100" 
                                        onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name'], ENT_QUOTES); ?>', <?php echo $item['price']; ?>)">
                                    <i data-lucide="plus"></i>
                                    เพิ่มลงตะกร้า
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/restaurant-qrcode/assets/js/main.js"></script>
<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Use session_token hash to separate cart for each customer session
const sessionHash = '<?php echo substr(md5($session_token), 0, 8); ?>';
const cartKey = 'cart_<?php echo $table['id']; ?>_' + sessionHash;

// Clear old cart data from previous sessions (keep only current session)
Object.keys(localStorage).forEach(key => {
    if (key.startsWith('cart_<?php echo $table['id']; ?>_') && key !== cartKey) {
        localStorage.removeItem(key);
    }
});

let cart = JSON.parse(localStorage.getItem(cartKey)) || [];

function updateCartDisplay() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const cartFooter = document.getElementById('cartFooter');
    const cartTotal = document.getElementById('cartTotal');
    
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    cartCount.textContent = totalItems;
    
    if (cart.length === 0) {
        cartItems.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i data-lucide="shopping-cart" style="width: 60px; height: 60px; opacity: 0.3;"></i>
                <p class="mt-3">ตะกร้าว่าง</p>
            </div>
        `;
        cartFooter.style.display = 'none';
    } else {
        let itemsHtml = '';
        let total = 0;
        
        cart.forEach(item => {
            const subtotal = item.price * item.quantity;
            total += subtotal;
            
            itemsHtml += `
                <div class="cart-item">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${item.name}</h6>
                            <p class="text-muted small mb-0">฿${item.price.toFixed(2)} x ${item.quantity}</p>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                            <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                        </button>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="quantity-control">
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateQuantity(${item.id}, -1)">
                                <i data-lucide="minus" style="width: 16px; height: 16px;"></i>
                            </button>
                            <span class="fw-bold">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-primary" onclick="updateQuantity(${item.id}, 1)">
                                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>
                        <span class="fw-bold text-primary">฿${subtotal.toFixed(2)}</span>
                    </div>
                </div>
            `;
        });
        
        cartItems.innerHTML = itemsHtml;
        cartTotal.textContent = '฿' + total.toFixed(2);
        cartFooter.style.display = 'block';
    }
    
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    localStorage.setItem(cartKey, JSON.stringify(cart));
}

function addToCart(id, name, price) {
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({ id, name, price, quantity: 1 });
    }
    
    updateCartDisplay();
    showToast('เพิ่ม ' + name + ' ลงตะกร้าแล้ว', 'success');
}

function updateQuantity(id, change) {
    const item = cart.find(item => item.id === id);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
        } else {
            updateCartDisplay();
        }
    }
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id !== id);
    updateCartDisplay();
}

function clearCart() {
    cart = [];
    localStorage.removeItem(cartKey);
    updateCartDisplay();
    showToast('ล้างตะกร้าสินค้าแล้ว', 'info');
}

function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    sidebar.classList.toggle('active');
}

async function confirmOrder() {
    if (cart.length === 0) {
        showToast('กรุณาเพิ่มเมนูลงตะกร้าก่อน', 'warning');
        return;
    }
    
    const customerName = document.getElementById('customerName').value.trim();
    
    showLoading();
    
    try {
        const response = await fetch('/restaurant-qrcode/api/create-order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                table_id: <?php echo $table['id']; ?>,
                session_token: '<?php echo $session_token; ?>',
                customer_name: customerName,
                items: cart
            })
        });
        
        const data = await response.json();
        
        hideLoading();
        
        if (data.success) {
            cart = [];
            localStorage.removeItem(cartKey); // Clear cart from localStorage
            updateCartDisplay();
            toggleCart();
            showToast('สั่งอาหารสำเร็จ! รอสักครู่นะคะ', 'success');
            document.getElementById('customerName').value = '';
        } else {
            showToast('เกิดข้อผิดพลาด: ' + data.message, 'danger');
        }
    } catch (error) {
        hideLoading();
        showToast('เกิดข้อผิดพลาด: ' + error.message, 'danger');
    }
}

// Initialize
updateCartDisplay();

// Re-initialize Lucide icons after page load (important for mobile)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});

// Also re-initialize after a short delay for slow mobile connections
setTimeout(function() {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}, 500);
</script>

</body>
</html>

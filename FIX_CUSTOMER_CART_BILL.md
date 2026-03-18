# คำแนะนำการแก้ไขปัญหา: ลูกค้าใหม่เห็นรายการอาหารของลูกค้าเก่า

## ปัญหาที่พบ
เมื่อชำระเงินและสร้าง Token ใหม่แล้ว ลูกค้าคนใหม่ยังเห็นรายการอาหารและบิลของลูกค้าคนเก่า

## สาเหตุ
1. **Cart (ตะกร้าสินค้า)**: ใช้ localStorage เก็บข้อมูลตาม `table_id` เท่านั้น ไม่ได้แยกตาม `session_token`
2. **Bill (บิล)**: Query ข้อมูล Orders โดยดูเฉพาะ `table_id` ไม่ได้กรองด้วย `session_token`
3. **สถานะ Orders**: Orders ที่ชำระเงินแล้วยังมีสถานะเป็น `served` ไม่ใช่ `completed`

## การแก้ไข

### 1. แก้ไข Cart (customer/order.php) ✅
```javascript
// เปลี่ยนจาก: cart_{table_id}
// เป็น: cart_{table_id}_{token_hash}
const sessionHash = '<?php echo substr(md5($session_token), 0, 8); ?>';
const cartKey = 'cart_<?php echo $table['id']; ?>_' + sessionHash;

// ลบ Cart เก่าอัตโนมัติ
Object.keys(localStorage).forEach(key => {
    if (key.startsWith('cart_<?php echo $table['id']; ?>_') && key !== cartKey) {
        localStorage.removeItem(key);
    }
});
```

### 2. แก้ไข Bill Query (customer/bill.php) ✅
```php
// เพิ่มเงื่อนไข: AND o.session_token = :session_token
$query = "SELECT o.*, 
          (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
          FROM orders o
          WHERE o.table_id = :table_id 
          AND o.session_token = :session_token
          AND o.status IN ('pending', 'preparing', 'ready', 'served')
          ORDER BY o.created_at DESC";
```

### 3. อัปเดตสถานะ Orders เมื่อชำระเงิน (admin/checkout.php) ✅
```php
// เปลี่ยนจาก: status = 'served'
// เป็น: status = 'completed', paid_at = NOW()
$query = "UPDATE orders SET status = 'completed', paid_at = NOW() WHERE id IN ($placeholders)";
```

### 4. เพิ่มสถานะ 'completed' และคอลัมน์ 'paid_at' ✅

**สำหรับฐานข้อมูลใหม่:** แก้ไขไฟล์ `database.sql` แล้ว

**สำหรับฐานข้อมูลที่มีอยู่แล้ว:** รันไฟล์ `migration_add_completed_status.sql`

```bash
# วิธีรัน Migration
cd C:\xampp\htdocs\restaurant-qrcode
mysql -u root restaurant_qrcode < migration_add_completed_status.sql
```

## วิธีทดสอบ

### ทดสอบ Cart
1. สแกน QR Code โต๊ะ T01 → สั่งอาหาร → เพิ่มลงตะกร้า
2. Admin: ชำระเงินโต๊ะ T01 (Token จะถูกสร้างใหม่)
3. พิมพ์ QR Code ใหม่ → สแกนอีกครั้ง
4. ✅ ตะกร้าต้องว่างเปล่า (ไม่มีของเก่า)

### ทดสอบ Bill
1. ลูกค้าคนที่ 1: สแกน QR → สั่งอาหาร → เช็คบิล (เห็นรายการตัวเอง)
2. Admin: ชำระเงิน → Token ใหม่ถูกสร้าง
3. ลูกค้าคนที่ 2: สแกน QR ใหม่ → เช็คบิล
4. ✅ ต้องแสดง "ยังไม่มีออเดอร์" (ไม่เห็นของลูกค้าคนที่ 1)

### ทดสอบ Admin
1. Admin: เช็คบิลโต๊ะใดก็ได้
2. ✅ ต้องเห็น Orders ของทุก Session (ทั้งเก่าและใหม่)
3. ชำระเงิน → Orders เปลี่ยนสถานะเป็น `completed`
4. ✅ Orders ที่ `completed` จะหายจากหน้า Kitchen Display

## ความปลอดภัย

| ฟีเจอร์ | ก่อนแก้ไข | หลังแก้ไข |
|---------|-----------|-----------|
| Cart Key | `cart_1` | `cart_1_a1b2c3d4` |
| Bill Query | `table_id` only | `table_id` + `session_token` |
| Payment Status | `served` | `completed` + `paid_at` |
| Token Regeneration | ❌ | ✅ หลังชำระเงิน |
| Old Cart Cleanup | ❌ | ✅ อัตโนมัติ |

## สรุปไฟล์ที่แก้ไข
- ✅ `customer/order.php` - Cart key + cleanup
- ✅ `customer/bill.php` - เพิ่ม session_token filter
- ✅ `admin/checkout.php` - สถานะ completed + paid_at
- ✅ `database.sql` - เพิ่ม enum 'completed' + คอลัมน์ paid_at
- ✅ `migration_add_completed_status.sql` - Migration script (ใหม่)

## หมายเหตุ
- Kitchen Display (`chef/kitchen.php`) ไม่ต้องแก้ เพราะแสดงแค่ `pending, preparing, ready`
- Orders ที่ชำระเงินแล้ว (completed) จะไม่แสดงในหน้า Customer Bill และ Kitchen Display
- Admin ยังเห็น Orders ทั้งหมดของโต๊ะ (สำหรับเช็คประวัติ)

# 🚀 คู่มือการติดตั้งและใช้งาน Restaurant QR Ordering System

## 📋 ขั้นตอนการติดตั้ง

### 1. ตรวจสอบ Requirements
- ✅ XAMPP (PHP 8.1+, MySQL 8.1+)
- ✅ Web Browser (Chrome, Firefox, Safari, Edge)
- ✅ เครื่องที่มีกล้องสำหรับสแกน QR Code (มือถือ)

### 2. ติดตั้งระบบ

#### 2.1 คัดลอกไฟล์
```bash
# ไฟล์โปรเจคอยู่ที่
C:\xampp\htdocs\restaurant-qrcode
```

#### 2.2 เปิด XAMPP
1. เปิดโปรแกรม XAMPP Control Panel
2. Start Apache
3. Start MySQL

#### 2.3 สร้างฐานข้อมูล
1. เปิด Browser ไปที่ `http://localhost/phpmyadmin`
2. คลิก "New" เพื่อสร้างฐานข้อมูลใหม่
3. ตั้งชื่อ: `restaurant_qrcode`
4. Collation: `utf8mb4_unicode_ci`
5. คลิก "Create"

#### 2.4 Import Database
1. คลิกที่ฐานข้อมูล `restaurant_qrcode`
2. คลิกแท็บ "Import"
3. คลิก "Choose File" เลือกไฟล์ `database.sql`
4. คลิก "Go" เพื่อ Import

หรือรันคำสั่งใน SQL:
```sql
-- คัดลอกเนื้อหาทั้งหมดจากไฟล์ database.sql แล้ววางที่นี่
```

#### 2.5 ตรวจสอบ Config
เปิดไฟล์ `config/database.php` ตรวจสอบการตั้งค่า:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'restaurant_qrcode');
define('DB_USER', 'root');
define('DB_PASS', '');  // ถ้ามี password ให้ใส่ตรงนี้
```

### 3. เปิดใช้งานระบบ

เปิด Browser ไปที่:
```
http://localhost/restaurant-qrcode
```

✅ ถ้าเห็นหน้า Landing Page แสดงว่าติดตั้งสำเร็จ!

---

## 👥 บัญชีสำหรับทดสอบ

### 🔑 Admin Account
```
Username: admin
Password: password
```
**สิทธิ์:**
- จัดการเมนูอาหาร (เพิ่ม/แก้ไข/ลบ)
- จัดการโต๊ะและ QR Code
- ดูรายงานและสถิติ
- ดูออเดอร์ทั้งหมด

### 👨‍🍳 Chef Account
```
Username: chef
Password: password
```
**สิทธิ์:**
- ดู Kitchen Display System
- อัปเดตสถานะอาหาร (รอ → ปรุง → พร้อม → เสิร์ฟ)

---

## 🧪 ทดสอบระบบ

### Test Case 1: Admin - จัดการเมนู

1. **Login เป็น Admin**
   - ไปที่ http://localhost/restaurant-qrcode/auth/login.php
   - Username: `admin`, Password: `password`
   - ✅ ควรเข้าสู่ Dashboard

2. **ดู Dashboard**
   - ✅ แสดงสถิติ: ออเดอร์วันนี้, รายได้, โต๊ะที่ใช้งาน
   - ✅ แสดงออเดอร์ล่าสุด (ถ้ามี)

3. **เพิ่มเมนูใหม่**
   - คลิก "จัดการเมนู" ที่เมนู
   - คลิกปุ่ม "เพิ่มเมนูใหม่"
   - กรอกข้อมูล:
     - ชื่อ: ข้าวมันไก่
     - หมวด: อาหารจานหลัก
     - ราคา: 45
     - รูปภาพ: (ถ้ามี)
   - คลิก "บันทึก"
   - ✅ ควรเห็นเมนูใหม่ในรายการ

4. **แก้ไขเมนู**
   - คลิกปุ่ม "แก้ไข" ที่เมนูใดๆ
   - เปลี่ยนราคาเป็น 50
   - คลิก "บันทึกการแก้ไข"
   - ✅ ควรเห็นราคาเปลี่ยน

### Test Case 2: QR Code และการสั่งอาหาร

1. **ดู QR Code**
   - Login เป็น Admin
   - คลิก "จัดการโต๊ะ"
   - เลือกโต๊ะใดก็ได้ (เช่น T01)
   - คลิก "ดู QR Code"
   - ✅ ควรเห็น QR Code พร้อม URL

2. **ทดสอบสั่งอาหาร (PC)**
   - คลิกปุ่ม "ทดสอบสั่งอาหาร" (หรือคัดลอก URL)
   - ✅ ควรเห็นหน้าเมนูอาหารพร้อมโต๊ะ
   - คลิก "เพิ่มลงตะกร้า" ที่เมนูต่างๆ
   - คลิกไอคอนตะกร้าสินค้า (ขวาบน)
   - ✅ ควรเห็นรายการในตะกร้า
   - ปรับจำนวนด้วยปุ่ม +/-
   - กรอกชื่อ (ถ้าต้องการ)
   - คลิก "ยืนยันการสั่ง"
   - ✅ ควรแสดงข้อความ "สั่งอาหารสำเร็จ"

3. **ทดสอบด้วยมือถือ (แนะนำ)**
   - หา IP ของเครื่อง PC (เช่น 192.168.1.100)
   - สแกน QR Code ด้วยกล้องมือถือ
   - หรือพิมพ์ URL: `http://192.168.1.100/restaurant-qrcode/customer/order.php?table=T01&token=xxx`
   - ทดสอบสั่งอาหารเหมือนข้างบน

### Test Case 3: Kitchen Display System

1. **Login เป็น Chef**
   - Logout จาก Admin
   - Login ด้วย Username: `chef`, Password: `password`
   - ✅ ควรเข้าสู่หน้า Kitchen Display โดยอัตโนมัติ

2. **ดูและจัดการออเดอร์**
   - ✅ ควรเห็น 3 คอลัมน์:
     - รอดำเนินการ (สีเหลือง)
     - กำลังปรุง (สีน้ำเงิน)
     - พร้อมเสิร์ฟ (สีเขียว)
   - ถ้ามีออเดอร์ใน "รอดำเนินการ":
     - คลิก "เริ่มปรุง"
     - ✅ ออเดอร์ควรย้ายไปคอลัมน "กำลังปรุง"
   - คลิก "พร้อมเสิร์ฟ"
     - ✅ ออเดอร์ควรย้ายไปคอลัมน "พร้อมเสิร์ฟ"
   - คลิก "เสิร์ฟแล้ว"
     - ✅ ออเดอร์ควรหายจากหน้าจอ

3. **Auto-Refresh**
   - เปิดหน้าสั่งอาหารในแท็บใหม่
   - สั่งอาหารใหม่
   - กลับมาที่หน้า Kitchen Display
   - ✅ ควรเห็นออเดอร์ใหม่ภายใน 30 วินาที (หรือกด Refresh)

### Test Case 4: ดูออเดอร์และรายงาน

1. **Login เป็น Admin**
2. **ดูออเดอร์ทั้งหมด**
   - คลิก "ออเดอร์" ที่เมนู
   - ✅ ควรเห็นรายการออเดอร์ทั้งหมด
   - ลอง Filter ตามสถานะ
   - ลอง Filter ตามวันที่

3. **ดูรายละเอียดออเดอร์**
   - คลิก "ดู" ที่ออเดอร์ใดๆ
   - ✅ ควรเห็น:
     - ข้อมูลออเดอร์ (โต๊ะ, วันที่, สถานะ)
     - รายการอาหารทั้งหมด
     - ยอดรวม

---

## 🐛 แก้ไขปัญหาที่พบบ่อย

### ❌ ปัญหา: ไม่สามารถเชื่อมต่อฐานข้อมูล

**วิธีแก้:**
1. ตรวจสอบ MySQL เปิดอยู่ใน XAMPP
2. ตรวจสอบชื่อ database ใน `config/database.php`
3. ตรวจสอบ username/password

### ❌ ปัญหา: QR Code ไม่แสดง

**วิธีแก้:**
1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต (ใช้ Google Charts API)
2. ลองรีเฟรชหน้า

### ❌ ปัญหา: อัปโหลดรูปไม่ได้

**วิธีแก้:**
1. ตรวจสอบโฟลเดอร์ `uploads/menu` มีอยู่หรือไม่
2. ตรวจสอบสิทธิ์ของโฟลเดอร์ (ควรเป็น 755)
3. ตรวจสอบขนาดไฟล์ไม่เกิน 5MB
4. ตรวจสอบไฟล์เป็น JPG, PNG เท่านั้น

### ❌ ปัญหา: หน้าจอขาว (White Screen)

**วิธีแก้:**
1. เปิด error reporting ใน PHP
2. ดู error log ใน XAMPP
3. ตรวจสอบ syntax error ในไฟล์ PHP

### ❌ ปัญหา: Icons ไม่แสดง

**วิธีแก้:**
1. ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต (Lucide ใช้ CDN)
2. ตรวจสอบ JavaScript console ว่ามี error หรือไม่

---

## 📱 ทดสอบบนมือถือ

### หา IP Address ของเครื่อง PC

**Windows:**
```cmd
ipconfig
```
มองหา "IPv4 Address" (เช่น 192.168.1.100)

**เปิดใช้งานบนมือถือ:**
1. ต้องเชื่อมต่อ WiFi เดียวกับ PC
2. เปิด Browser บนมือถือ
3. พิมพ์: `http://192.168.1.100/restaurant-qrcode`
4. สแกน QR Code จากหน้าจัดการโต๊ะ

---

## 🔒 Security Checklist

สำหรับ Production:
- [ ] เปลี่ยนรหัสผ่าน default ทั้งหมด
- [ ] ตั้งค่า database password
- [ ] เปิด HTTPS
- [ ] ตั้งค่า permissions ของโฟลเดอร์ให้ถูกต้อง
- [ ] Backup database เป็นประจำ
- [ ] อัปเดต PHP และ MySQL เป็นเวอร์ชันล่าสุด

---

## 📚 เอกสารเพิ่มเติม

### โครงสร้าง URL

```
Landing Page:        http://localhost/restaurant-qrcode/
Admin Login:         http://localhost/restaurant-qrcode/auth/login.php
Admin Dashboard:     http://localhost/restaurant-qrcode/admin/dashboard.php
Chef Kitchen:        http://localhost/restaurant-qrcode/chef/kitchen.php
Customer Order:      http://localhost/restaurant-qrcode/customer/order.php?table=T01&token=xxx
```

### Database Schema

**Tables:**
- `users` - ผู้ใช้งาน (admin, chef)
- `tables` - โต๊ะพร้อม QR token
- `categories` - หมวดหมู่เมนู
- `menu_items` - รายการเมนู
- `orders` - ออเดอร์
- `order_items` - รายการในออเดอร์

---

## ✅ Checklist การทดสอบ

- [ ] ติดตั้งฐานข้อมูลสำเร็จ
- [ ] Login Admin ได้
- [ ] Login Chef ได้
- [ ] เพิ่ม/แก้ไข/ลบเมนูได้
- [ ] ดู QR Code ได้
- [ ] สั่งอาหารผ่านหน้าลูกค้าได้
- [ ] ออเดอร์แสดงใน Kitchen Display
- [ ] อัปเดตสถานะอาหารได้
- [ ] ดูรายละเอียดออเดอร์ได้
- [ ] ทดสอบบนมือถือสำเร็จ

---

## 🎉 พร้อมใช้งาน!

ระบบพร้อมใช้งานแล้ว ขอให้สนุกกับการทดสอบและพัฒนาต่อครับ!

**ติดต่อสอบถาม:**
- อ่านไฟล์ README.md สำหรับรายละเอียดเพิ่มเติม
- ตรวจสอบ Code ในโฟลเดอร์ต่างๆ
- ทดลองปรับแต่งตาม requirements ของคุณ

---

**Version:** 1.0.0  
**Last Updated:** January 2026

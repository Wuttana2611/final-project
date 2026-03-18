# 🍽️ Restaurant QR Code Ordering System

ระบบสั่งอาหารผ่าน QR Code ที่ทันสมัยและใช้งานง่าย พัฒนาด้วย PHP 8.1, MySQL 8.1 และ Bootstrap 5

## ✨ Features

### 🔐 Authentication System
- **Admin** - จัดการระบบทั้งหมด (เมนู, โต๊ะ, ออเดอร์)
- **Chef** - ดู Kitchen Display System และอัปเดตสถานะอาหาร
- **Customer** - สั่งอาหารผ่าน QR Code

### 📱 QR Code Ordering
- สร้าง QR Code สำหรับแต่ละโต๊ะพร้อม Token
- ลูกค้าสแกนเพื่อเข้าหน้าสั่งอาหาร
- ระบบตะกร้าสินค้า (Cart System)
- ยืนยันออเดอร์พร้อม Session Token

### 👨‍🍳 Kitchen Display System (KDS)
- แสดงออเดอร์แบบ Kanban (รอดำเนินการ, กำลังปรุง, พร้อมเสิร์ฟ)
- อัปเดตสถานะแบบ Real-time
- Auto-refresh ทุก 30 วินาที
- แสดงเวลาที่ผ่านไปของแต่ละออเดอร์

### 📊 Admin Dashboard
- สรุปยอดขายและสถิติ
- จัดการเมนูอาหาร (CRUD)
- จัดการโต๊ะและ QR Code
- ดูประวัติออเดอร์ทั้งหมด

## 🎨 UI/UX Design

- **Framework**: Bootstrap 5
- **Color Theme**: Orange (#E67E22) - White - Dark
- **Typography**: Google Font "Prompt" (สำหรับภาษาไทย)
- **Icons**: Lucide Icons
- **Responsive**: รองรับทุกขนาดหน้าจอ

## 🛠️ Technology Stack

- **Backend**: PHP 8.1 with PDO
- **Database**: MySQL 8.1
- **Frontend**: Bootstrap 5, JavaScript ES6+
- **Icons**: Lucide
- **Security**: Prepared Statements, Session Management

## 📁 Project Structure

```
restaurant-qrcode/
├── admin/               # Admin pages
│   ├── dashboard.php
│   ├── menu.php
│   ├── menu-add.php
│   ├── tables.php
│   ├── table-qr.php
│   └── orders.php
├── chef/                # Chef pages
│   └── kitchen.php
├── customer/            # Customer pages
│   └── order.php
├── auth/                # Authentication
│   ├── login.php
│   └── logout.php
├── api/                 # API endpoints
│   └── create-order.php
├── config/              # Configuration
│   ├── database.php
│   └── session.php
├── includes/            # Reusable components
│   ├── header.php
│   └── footer.php
├── assets/              # Static assets
│   ├── css/
│   │   └── style.css
│   └── js/
│       ├── main.js
│       └── kitchen.js
├── uploads/             # Upload directory
│   └── menu/
├── database.sql         # Database schema
└── index.php            # Landing page
```

## 🚀 Installation

### 1. Prerequisites
- XAMPP (PHP 8.1+ และ MySQL 8.1+)
- Web Browser

### 2. Setup Steps

1. **Clone/Copy โปรเจค**
   ```bash
   # คัดลอกโปรเจคไปที่ htdocs
   c:\xampp\htdocs\restaurant-qrcode
   ```

2. **สร้างฐานข้อมูล**
   - เปิด phpMyAdmin (http://localhost/phpmyadmin)
   - Import ไฟล์ `database.sql`
   - หรือรันคำสั่ง SQL ในไฟล์

3. **ตั้งค่าฐานข้อมูล**
   - เปิดไฟล์ `config/database.php`
   - แก้ไขข้อมูลการเชื่อมต่อ (ถ้าจำเป็น)
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'restaurant_qrcode');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

4. **สร้างโฟลเดอร์สำหรับอัปโหลด**
   ```bash
   mkdir uploads/menu
   ```

5. **เปิดเว็บไซต์**
   ```
   http://localhost/restaurant-qrcode
   ```

## 👤 Default Accounts

### Admin Account
- **Username**: `admin`
- **Password**: `password`

### Chef Account
- **Username**: `chef`
- **Password**: `password`

## 📖 Usage Guide

### สำหรับ Admin

1. **Login** ที่หน้า `/auth/login.php`
2. **จัดการเมนู** - เพิ่ม/แก้ไข/ลบเมนูอาหาร
3. **จัดการโต๊ะ** - ดู QR Code และพิมพ์ไว้ที่โต๊ะ
4. **ดู Dashboard** - ตรวจสอบยอดขายและสถิติ

### สำหรับ Chef

1. **Login** ด้วยบัญชี Chef
2. เข้า **Kitchen Display** - จะเห็นออเดอร์ทั้งหมด
3. **อัปเดตสถานะ** - กดปุ่มเพื่อเปลี่ยนสถานะอาหาร

### สำหรับลูกค้า

1. **สแกน QR Code** ที่โต๊ะ
2. **เลือกเมนู** และใส่ตะกร้า
3. **ยืนยันออเดอร์** พร้อมกรอกชื่อ (ถ้าต้องการ)

## 🔒 Security Features

- ✅ Prepared Statements (ป้องกัน SQL Injection)
- ✅ Session Management
- ✅ Password Hashing (password_verify)
- ✅ Token-based Table Access
- ✅ Role-based Authorization

## 🎯 Key Features Implementation

### 1. QR Code Generation
- ใช้ Google Charts API
- URL format: `/customer/order.php?table=T01&token=xxxxx`
- Token unique สำหรับแต่ละโต๊ะ

### 2. Cart System
- เก็บข้อมูลใน LocalStorage
- Persist ข้ามการรีเฟรช
- Real-time calculation

### 3. Order Flow
```
Customer Order → Pending → Preparing → Ready → Served
                    ↓
                Kitchen Display System
```

### 4. Database Relationships
- Orders ↔ Tables (Foreign Key)
- Orders ↔ Order Items (Foreign Key)
- Order Items ↔ Menu Items (Foreign Key)

## 🎨 Color Scheme

```css
--primary-color: #E67E22   /* Orange */
--primary-dark: #D35400    /* Dark Orange */
--success-color: #27AE60   /* Green */
--danger-color: #E74C3C    /* Red */
--warning-color: #F39C12   /* Yellow */
--dark-color: #2C3E50      /* Dark Blue */
```

## 📱 Responsive Breakpoints

- **Mobile**: < 768px
- **Tablet**: 768px - 992px
- **Desktop**: > 992px

## 🔄 Auto-Refresh

Kitchen Display รีเฟรชอัตโนมัติทุก 30 วินาที เพื่อให้เห็นออเดอร์ใหม่

## 📝 Sample Data

ระบบมาพร้อมข้อมูลตัวอย่าง:
- 4 หมวดหมู่เมนู
- 10 เมนูอาหาร
- 10 โต๊ะ

## 🐛 Troubleshooting

### ปัญหาที่พบบ่อย

1. **ไม่สามารถเชื่อมต่อฐานข้อมูล**
   - ตรวจสอบ XAMPP MySQL เปิดอยู่หรือไม่
   - ตรวจสอบ config/database.php

2. **รูปภาพไม่แสดง**
   - ตรวจสอบ folder `uploads/menu` มีสิทธิ์ write
   - ตรวจสอบชื่อไฟล์รูปภาพ

3. **QR Code ไม่ทำงาน**
   - ตรวจสอบ URL ใน table-qr.php
   - ตรวจสอบ session_token ในฐานข้อมูล

## 🚀 Future Enhancements

- [ ] ระบบชำระเงินออนไลน์
- [ ] Line Notify สำหรับออเดอร์ใหม่
- [ ] รายงานการขาย (Sales Report)
- [ ] ระบบจัดการพนักงาน
- [ ] Multi-language Support
- [ ] PWA (Progressive Web App)

## 👨‍💻 Developer

Developed by Full-stack Developer

- PHP 8.1
- MySQL 8.1
- Bootstrap 5
- JavaScript ES6+

## 📄 License

This project is open-source and available for educational purposes.

---

**วิธีทดสอบระบบ:**

1. Login เป็น Admin → จัดการเมนู
2. ไปหน้าจัดการโต๊ะ → ดู QR Code
3. คลิก "ทดสอบสั่งอาหาร" → เพิ่มเมนูลงตะกร้า
4. ยืนยันออเดอร์
5. Login เป็น Chef → ดูออเดอร์ใน Kitchen Display
6. อัปเดตสถานะอาหาร

🎉 **Happy Coding!**

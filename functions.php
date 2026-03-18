<?php
/**
 * Helper Functions for Restaurant QR System
 */

// Format currency
function formatCurrency($amount) {
    return '฿' . number_format($amount, 2);
}

// Format date time Thai
function formatDateTimeThai($datetime) {
    $thai_months = [
        1 => 'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน',
        'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม',
        'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    $timestamp = strtotime($datetime);
    $day = date('j', $timestamp);
    $month = $thai_months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    $time = date('H:i', $timestamp);
    
    return "$day $month $year เวลา $time น.";
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Check file type
function isValidImageType($file_type) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    return in_array($file_type, $allowed_types);
}

// Time ago function
function timeAgo($datetime) {
    $time_ago = time() - strtotime($datetime);
    
    if ($time_ago < 60) {
        return 'เมื่อสักครู่';
    } elseif ($time_ago < 3600) {
        $minutes = floor($time_ago / 60);
        return $minutes . ' นาทีที่แล้ว';
    } elseif ($time_ago < 86400) {
        $hours = floor($time_ago / 3600);
        return $hours . ' ชั่วโมงที่แล้ว';
    } else {
        $days = floor($time_ago / 86400);
        return $days . ' วันที่แล้ว';
    }
}

// Status badge helper
function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge status-pending">รอดำเนินการ</span>',
        'preparing' => '<span class="badge status-preparing">กำลังปรุง</span>',
        'ready' => '<span class="badge status-ready">พร้อมเสิร์ฟ</span>',
        'served' => '<span class="badge status-served">เสิร์ฟแล้ว</span>',
        'cancelled' => '<span class="badge status-cancelled">ยกเลิก</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

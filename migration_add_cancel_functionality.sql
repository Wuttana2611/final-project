-- Add cancellation functionality to order_items table
-- This migration adds the ability for customers to cancel individual items

USE restaurant_qrcode;

-- Add cancelled_quantity field to order_items
ALTER TABLE order_items 
ADD COLUMN cancelled_quantity INT DEFAULT 0 AFTER quantity,
ADD COLUMN cancelled_at TIMESTAMP NULL AFTER cancelled_quantity,
ADD COLUMN can_cancel BOOLEAN DEFAULT TRUE AFTER cancelled_at;

-- Add index for better performance on cancellation queries
CREATE INDEX idx_order_items_cancelled ON order_items(cancelled_quantity);
CREATE INDEX idx_order_items_can_cancel ON order_items(can_cancel);
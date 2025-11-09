-- Add discount feature to orders table
-- Migration date: November 2025
-- Adds discount amount and discount percentage fields for flexible discount handling

-- Add discount columns to orders table
ALTER TABLE orders 
ADD COLUMN discount_type ENUM('none', 'fixed', 'percentage') DEFAULT 'none' AFTER balance_due,
ADD COLUMN discount_value DECIMAL(10, 2) DEFAULT 0.00 AFTER discount_type,
ADD COLUMN subtotal DECIMAL(10, 2) DEFAULT 0.00 AFTER discount_value;

-- Update existing orders to set subtotal = total_cost for backward compatibility
UPDATE orders SET subtotal = total_cost WHERE subtotal = 0;

-- Add index for discount queries
ALTER TABLE orders ADD INDEX idx_discount_type (discount_type);

-- Verify the changes
SHOW COLUMNS FROM orders WHERE Field IN ('discount_type', 'discount_value', 'subtotal');

-- Show success message
SELECT 'Discount feature added successfully! Orders table now supports discounts.' AS message;

-- Notes:
-- discount_type: 'none' (no discount), 'fixed' (fixed amount), 'percentage' (percentage off)
-- discount_value: The discount amount or percentage value
-- subtotal: The cost before discount (sum of all costs)
-- total_cost: The final cost after discount is applied
-- 
-- Calculation logic:
-- subtotal = vehicle_price + car_cost + transportation + duty + clearing + fixing
-- discount_amount = (discount_type = 'fixed') ? discount_value : (subtotal * discount_value / 100)
-- total_cost = subtotal - discount_amount
-- balance_due = total_cost - total_deposits


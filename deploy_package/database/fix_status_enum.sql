-- Fix orders.status ENUM to match the expected values
-- This updates the existing database table to include all required status values
-- NOTE: Values must be capitalized to match database/schema.sql and application code

ALTER TABLE orders 
MODIFY COLUMN status ENUM(
    'Pending', 
    'Purchased', 
    'Shipping', 
    'Customs', 
    'Inspection', 
    'Repair', 
    'Ready', 
    'Delivered', 
    'Cancelled'
) DEFAULT 'Pending';

-- Verify the change
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME = 'orders' 
  AND COLUMN_NAME = 'status';


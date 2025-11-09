-- Update order statuses to include more detailed shipping tracking
-- Migration date: November 2025
-- This updates the orders.status ENUM to include new status stages

ALTER TABLE orders 
MODIFY COLUMN status ENUM(
    'Pending',
    'Purchased', 
    'Delivered to Port of Load',
    'Origin customs clearance',
    'Shipping',
    'Arrived in Ghana',
    'Ghana Customs Clearance',
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

-- Show message
SELECT 'Order statuses updated successfully!' AS message;


-- Update email notification settings for new order statuses
-- Migration date: November 2025

-- Add new status email notification settings
INSERT INTO email_notification_settings (setting_key, setting_value, description) VALUES
('email_status_delivered_to_port_of_load', '1', 'Send email for Delivered to Port of Load status'),
('email_status_origin_customs_clearance', '1', 'Send email for Origin customs clearance status'),
('email_status_arrived_in_ghana', '1', 'Send email for Arrived in Ghana status'),
('email_status_ghana_customs_clearance', '1', 'Send email for Ghana Customs Clearance status')
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    description = VALUES(description);

-- Update existing Customs status key to Ghana Customs Clearance for clarity
-- Note: Keep the old key for backward compatibility, but we'll phase it out
UPDATE email_notification_settings 
SET description = 'Send email for Ghana Customs Clearance status (legacy)' 
WHERE setting_key = 'email_status_customs';

-- Verify the changes
SELECT setting_key, setting_value, description 
FROM email_notification_settings 
WHERE setting_key LIKE 'email_status_%' 
ORDER BY setting_key;


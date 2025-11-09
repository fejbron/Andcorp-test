<?php

class Notification {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($userId, $orderId, $type, $subject, $message) {
        // Validate and sanitize inputs
        $userId = Security::sanitizeInt($userId);
        $orderId = $orderId ? Security::sanitizeInt($orderId) : null;
        $type = Security::validateEnum($type, ['email', 'sms', 'push']) ? $type : 'email';
        $subject = Security::sanitizeString($subject, 255);
        $message = Security::sanitizeString($message, 5000);
        
        if ($userId <= 0) {
            return false;
        }
        
        // Check if email notifications are enabled (for email type only)
        if ($type === 'email') {
            $settings = new Settings();
            if (!$settings->isEmailNotificationsEnabled()) {
                error_log("Email notifications are disabled in settings. Skipping notification.");
                return false;
            }
        }
        
        $sql = "INSERT INTO notifications (user_id, order_id, type, subject, message, status) 
                VALUES (:user_id, :order_id, :type, :subject, :message, 'pending')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':type' => $type,
            ':subject' => $subject,
            ':message' => $message
        ]);
        
        $notificationId = $this->db->lastInsertId();
        
        // Send notification based on type
        if ($type === 'email') {
            $this->sendEmail($notificationId);
        } elseif ($type === 'sms') {
            $this->sendSMS($notificationId);
        }
        
        return $notificationId;
    }
    
    public function sendOrderUpdate($orderId, $status, $additionalMessage = '') {
        // Check if email notifications are enabled for this status
        $settings = new Settings();
        if (!$settings->shouldSendEmailForStatus($status)) {
            error_log("Email notification skipped for status: {$status} (disabled in settings)");
            return false;
        }
        
        $orderModel = new Order();
        $order = $orderModel->findById($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Normalize status to lowercase for message lookup
        $statusLower = strtolower($status);
        
        $statusMessages = [
            'pending' => 'Your order has been created and is pending processing.',
            'purchased' => 'Your vehicle has been successfully purchased from the auction!',
            'delivered to port of load' => 'Your vehicle has been delivered to the port of loading and is being prepared for shipping.',
            'origin customs clearance' => 'Your vehicle is going through export customs clearance at the origin country.',
            'shipping' => 'Your vehicle is now being shipped to Ghana.',
            'arrived in ghana' => 'Great news! Your vehicle has arrived safely in Ghana.',
            'ghana customs clearance' => 'Your vehicle is going through customs clearance in Ghana.',
            'inspection' => 'Your vehicle is currently being inspected.',
            'repair' => 'Your vehicle is in the shop for repairs and improvements.',
            'ready' => 'Great news! Your vehicle is ready for delivery.',
            'delivered' => 'Your vehicle has been delivered. Thank you for your business!',
            'cancelled' => 'Your order has been cancelled. If you have any questions, please contact us.'
        ];
        
        $subject = "Order Update: {$order['order_number']} - {$status}";
        $message = ($statusMessages[$statusLower] ?? 'Your order status has been updated.') . "\n\n" . $additionalMessage;
        
        // Get user from customer
        $customerModel = new Customer();
        $customer = $customerModel->findById($order['customer_id']);
        
        if (!$customer || !$customer['user_id']) {
            error_log("Cannot send email: Customer or user_id not found for order #{$orderId}");
            return false;
        }
        
        return $this->create($customer['user_id'], $orderId, 'email', $subject, $message);
    }
    
    public function sendTicketCreated($ticketId) {
        try {
            $ticketModel = new SupportTicket();
            $ticket = $ticketModel->findById($ticketId);
            
            if (!$ticket) {
                error_log("Cannot send ticket created email: Ticket #{$ticketId} not found");
                return false;
            }
            
            // Get all staff users (admin and staff roles)
            $db = Database::getInstance()->getConnection();
            $staffSql = "SELECT id, email, first_name FROM users WHERE role IN ('admin', 'staff')";
            $staffStmt = $db->query($staffSql);
            $staffUsers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($staffUsers)) {
                error_log("No staff users found to notify about ticket creation");
                return false;
            }
            
            $customerName = trim(($ticket['customer_first_name'] ?? '') . ' ' . ($ticket['customer_last_name'] ?? ''));
            $subject = "New Support Ticket: {$ticket['ticket_number']} - {$ticket['subject']}";
            
            $priorityLabel = match($ticket['priority']) {
                'urgent' => '🔴 URGENT',
                'high' => '🟠 HIGH',
                'normal' => '🟡 NORMAL',
                'low' => '🟢 LOW',
                default => $ticket['priority']
            };
            
            $message = "A new support ticket has been created:\n\n";
            $message .= "Ticket Number: {$ticket['ticket_number']}\n";
            $message .= "Customer: {$customerName}\n";
            $message .= "Subject: {$ticket['subject']}\n";
            $message .= "Priority: {$priorityLabel}\n";
            $message .= "Category: " . ucfirst($ticket['category']) . "\n";
            
            if (!empty($ticket['order_number'])) {
                $message .= "Related Order: {$ticket['order_number']}\n";
            }
            
            $message .= "\nPlease review and respond to this ticket as soon as possible.";
            
            // Send notification to all staff members
            $sentCount = 0;
            foreach ($staffUsers as $staff) {
                $result = $this->create($staff['id'], null, 'email', $subject, $message);
                if ($result) {
                    $sentCount++;
                }
            }
            
            error_log("Ticket created notification sent to {$sentCount} staff members for ticket #{$ticketId}");
            return $sentCount > 0;
            
        } catch (Exception $e) {
            error_log("Error sending ticket created notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTicketReply($ticketId, $replyId, $isStaffReply = false) {
        try {
            $ticketModel = new SupportTicket();
            $ticket = $ticketModel->findById($ticketId);
            
            if (!$ticket) {
                error_log("Cannot send ticket reply email: Ticket #{$ticketId} not found");
                return false;
            }
            
            // Get the reply details
            $db = Database::getInstance()->getConnection();
            $replySql = "SELECT tr.*, u.first_name, u.last_name 
                        FROM ticket_replies tr
                        LEFT JOIN users u ON tr.user_id = u.id
                        WHERE tr.id = ?";
            $replyStmt = $db->prepare($replySql);
            $replyStmt->execute([$replyId]);
            $reply = $replyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reply) {
                error_log("Cannot send ticket reply email: Reply #{$replyId} not found");
                return false;
            }
            
            $replyAuthor = trim(($reply['first_name'] ?? '') . ' ' . ($reply['last_name'] ?? ''));
            
            if ($isStaffReply) {
                // Staff replied - notify customer
                if (!$ticket['customer_user_id']) {
                    error_log("Cannot send ticket reply email: Customer user ID not found");
                    return false;
                }
                
                $subject = "Reply to Your Support Ticket: {$ticket['ticket_number']}";
                $message = "You have received a new reply to your support ticket:\n\n";
                $message .= "Ticket Number: {$ticket['ticket_number']}\n";
                $message .= "Subject: {$ticket['subject']}\n";
                $message .= "Reply from: {$replyAuthor} (Support Team)\n\n";
                $message .= "Message:\n{$reply['message']}\n\n";
                $message .= "You can view and respond to this ticket by logging into your account.";
                
                return $this->create($ticket['customer_user_id'], null, 'email', $subject, $message);
                
            } else {
                // Customer replied - notify assigned staff or all staff
                $subject = "Customer Reply on Ticket: {$ticket['ticket_number']}";
                $customerName = trim(($ticket['customer_first_name'] ?? '') . ' ' . ($ticket['customer_last_name'] ?? ''));
                
                $message = "A customer has replied to support ticket:\n\n";
                $message .= "Ticket Number: {$ticket['ticket_number']}\n";
                $message .= "Customer: {$customerName}\n";
                $message .= "Subject: {$ticket['subject']}\n\n";
                $message .= "Customer's Reply:\n{$reply['message']}\n\n";
                $message .= "Please review and respond to this ticket.";
                
                // If ticket is assigned, notify only the assigned staff
                if (!empty($ticket['assigned_to'])) {
                    return $this->create($ticket['assigned_to'], null, 'email', $subject, $message);
                } else {
                    // Otherwise, notify all staff
                    $staffSql = "SELECT id FROM users WHERE role IN ('admin', 'staff')";
                    $staffStmt = $db->query($staffSql);
                    $staffUsers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    $sentCount = 0;
                    foreach ($staffUsers as $staff) {
                        $result = $this->create($staff['id'], null, 'email', $subject, $message);
                        if ($result) {
                            $sentCount++;
                        }
                    }
                    
                    return $sentCount > 0;
                }
            }
            
        } catch (Exception $e) {
            error_log("Error sending ticket reply notification: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendTicketStatusUpdate($ticketId, $oldStatus, $newStatus) {
        try {
            $ticketModel = new SupportTicket();
            $ticket = $ticketModel->findById($ticketId);
            
            if (!$ticket) {
                error_log("Cannot send ticket status update email: Ticket #{$ticketId} not found");
                return false;
            }
            
            if (!$ticket['customer_user_id']) {
                error_log("Cannot send ticket status update email: Customer user ID not found");
                return false;
            }
            
            $statusMessages = [
                'open' => 'Your ticket has been opened and is being reviewed by our team.',
                'pending' => 'We are waiting for additional information from you.',
                'resolved' => 'Great news! Your ticket has been resolved. If the issue persists, please reply to reopen the ticket.',
                'closed' => 'Your ticket has been closed. Thank you for contacting us. If you need further assistance, please create a new ticket.'
            ];
            
            $statusEmoji = match($newStatus) {
                'open' => '📬',
                'pending' => '⏳',
                'resolved' => '✅',
                'closed' => '🔒',
                default => '📋'
            };
            
            $subject = "{$statusEmoji} Ticket Status Update: {$ticket['ticket_number']}";
            
            $message = "The status of your support ticket has been updated:\n\n";
            $message .= "Ticket Number: {$ticket['ticket_number']}\n";
            $message .= "Subject: {$ticket['subject']}\n";
            $message .= "Previous Status: " . ucfirst($oldStatus) . "\n";
            $message .= "New Status: " . ucfirst($newStatus) . "\n\n";
            $message .= ($statusMessages[$newStatus] ?? "Your ticket status has been updated.") . "\n\n";
            
            if ($newStatus === 'pending') {
                $message .= "Please log in to your account to view the ticket and provide the requested information.";
            } elseif ($newStatus !== 'closed' && $newStatus !== 'resolved') {
                $message .= "You can view the details and add comments by logging into your account.";
            }
            
            return $this->create($ticket['customer_user_id'], null, 'email', $subject, $message);
            
        } catch (Exception $e) {
            error_log("Error sending ticket status update notification: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendEmail($notificationId) {
        $sql = "SELECT n.*, u.email, u.first_name 
                FROM notifications n
                JOIN users u ON n.user_id = u.id
                WHERE n.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return false;
        }
        
        // Get email settings
        $settings = new Settings();
        $fromName = $settings->getEmailFromName();
        $fromAddress = $settings->getEmailFromAddress();
        $replyTo = $settings->getEmailReplyTo();
        
        // Email headers
        $headers = "From: {$fromName} <{$fromAddress}>\r\n";
        $headers .= "Reply-To: {$replyTo}\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        
        // Email body (sanitize for email)
        $firstName = Security::escape($notification['first_name']);
        $subject = Security::escape($notification['subject']);
        $message = Security::escape($notification['message']);
        $body = $this->getEmailTemplate($firstName, $subject, $message);
        
        // Send email
        $sent = mail($notification['email'], $subject, $body, $headers);
        
        // Update notification status
        $updateSql = "UPDATE notifications SET status = :status, sent_at = CURRENT_TIMESTAMP WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([
            ':id' => $notificationId,
            ':status' => $sent ? 'sent' : 'failed'
        ]);
        
        return $sent;
    }
    
    private function sendSMS($notificationId) {
        // Implement SMS sending logic here
        // This would integrate with your SMS provider (Twilio, Africa's Talking, etc.)
        
        if (getenv('SMS_ENABLED') !== 'true') {
            return false;
        }
        
        $sql = "SELECT n.*, u.phone, u.first_name 
                FROM notifications n
                JOIN users u ON n.user_id = u.id
                WHERE n.id = :id AND u.phone IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $notificationId]);
        $notification = $stmt->fetch();
        
        if (!$notification) {
            return false;
        }
        
        // TODO: Implement actual SMS sending
        // For now, mark as sent
        $updateSql = "UPDATE notifications SET status = 'sent', sent_at = CURRENT_TIMESTAMP WHERE id = :id";
        $updateStmt = $this->db->prepare($updateSql);
        return $updateStmt->execute([':id' => $notificationId]);
    }
    
    private function getEmailTemplate($name, $subject, $message) {
        // Inputs are already sanitized, but ensure HTML safety
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $message = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
        
        // Get absolute URL for logo
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'andcorpautos.com';
        $logoUrl = $protocol . '://' . $host . '/assets/images/logo.png';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset=\"UTF-8\">
            <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; 
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0; 
                    padding: 0;
                    background-color: #f4f4f4;
                }
                .container { 
                    max-width: 600px; 
                    margin: 20px auto; 
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .header { 
                    background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
                    color: white; 
                    padding: 30px 20px; 
                    text-align: center; 
                }
                .header img {
                    max-width: 150px;
                    height: auto;
                    margin-bottom: 10px;
                }
                .header h1 { 
                    margin: 10px 0 0 0; 
                    font-size: 24px;
                    font-weight: 600;
                    color: #1a1a1a;
                }
                .content { 
                    background-color: #ffffff; 
                    padding: 30px 25px; 
                }
                .content h2 {
                    color: #1a1a1a;
                    font-size: 20px;
                    margin-top: 0;
                    margin-bottom: 15px;
                }
                .content p {
                    margin: 15px 0;
                    color: #4a4a4a;
                }
                .footer { 
                    text-align: center; 
                    padding: 20px; 
                    font-size: 12px; 
                    color: #999;
                    background-color: #f9f9f9;
                    border-top: 1px solid #e0e0e0;
                }
                .footer a {
                    color: #4a90e2;
                    text-decoration: none;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <img src='{$logoUrl}' alt='Andcorp Autos Logo' />
                    <h1>Andcorp Autos</h1>
                </div>
                <div class='content'>
                    <p>Dear {$name},</p>
                    <h2>{$subject}</h2>
                    <p>{$message}</p>
                    <p>If you have any questions, please don't hesitate to contact us.</p>
                    <p style='margin-top: 25px;'>
                        <strong>Best regards,</strong><br>
                        Andcorp Autos Team
                    </p>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " <a href='https://andcorpautos.com' target='_blank'>Andcorp Autos</a>. All rights reserved.</p>
                    <p>
                        <a href='mailto:info@andcorpautos.com'>info@andcorpautos.com</a> | 
                        <a href='tel:+233249494091'>+233 24 949 4091</a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    public function getUserNotifications($userId, $unreadOnly = false) {
        $userId = Security::sanitizeInt($userId);
        if ($userId <= 0) {
            return [];
        }
        
        if ($unreadOnly) {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id AND read_at IS NULL ORDER BY created_at DESC LIMIT 50";
        } else {
            $sql = "SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 50";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    public function getById($notificationId) {
        $sql = "SELECT * FROM notifications WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => Security::sanitizeInt($notificationId)]);
        return $stmt->fetch();
    }
    
    public function markAsRead($notificationId) {
        $sql = "UPDATE notifications SET read_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => Security::sanitizeInt($notificationId)]);
    }
}

# Staff Role Permissions - Andcorp Autos

## Overview
The **Staff** role has access to most administrative functions, with the exception of system settings management which is restricted to **Admin** only.

**Note:** The `Auth::isStaff()` function returns `true` for both `'admin'` and `'staff'` roles, meaning staff have the same permissions as admin for most features.

---

## ✅ What Staff CAN Do

### 1. Dashboard & Statistics
- ✅ Access Admin Dashboard (`/admin/dashboard.php`)
- ✅ View order statistics (total orders, active orders, revenue)
- ✅ View customer statistics
- ✅ View deposit statistics
- ✅ See recent orders list

### 2. Order Management
- ✅ **View all orders** (`/admin/orders.php`)
  - Filter by status
  - Search orders
  - View order details
- ✅ **Create new orders** (`/admin/orders/create.php`)
  - Create orders for any customer
  - Enter vehicle details (make, model, year, VIN, etc.)
  - Set auction source (Copart, IAA, SCA Auction, TGNA, Manheim)
  - Set initial order status
- ✅ **Edit orders** (`/admin/orders/edit.php`)
  - Update order status
  - Update vehicle information
  - Update cost breakdown:
    - Vehicle purchase price
    - Additional car costs
    - Transportation cost
    - Duty cost
    - Clearing cost
    - Fixing cost
    - Total cost
  - Update deposit amount
  - Add notes
  - View deposit history

### 3. Customer Management
- ✅ **View all customers** (`/admin/customers.php`)
  - See customer list with order counts
  - View customer total spending
  - Access customer details

### 4. Deposit Management
- ✅ **View all deposits** (`/admin/deposits.php`)
  - Filter by status (pending, verified, rejected)
  - Search deposits
- ✅ **Add deposits** (`/admin/deposits/add.php`)
  - Add deposits for any order
  - Upload deposit slip images
  - Set deposit amount and date
- ✅ **View deposit details** (`/admin/deposits/view.php`)
  - View deposit information
  - Verify deposits
  - Reject deposits
  - Update deposit status to pending
  - View deposit history for orders

### 5. Quote Request Management
- ✅ **View quote requests** (`/admin/quote-requests.php`)
  - Filter by status
  - Search quote requests
- ✅ **View quote details** (`/admin/quote-requests/view.php`)
  - View full quote request details
  - See customer information
- ✅ **Convert quotes to orders** (`/admin/quote-requests/convert.php`)
  - Convert quote requests into orders

### 6. Document & Image Management
- ✅ **Upload documents** (`/orders/documents.php`)
  - Upload car images
  - Upload vehicle title documents
  - Upload Bill of Lading
  - Upload Bill of Entry/Duty documents
  - Delete uploaded documents
- ✅ **View all documents** for all orders
- ✅ **Access gallery** (`/gallery.php`)
  - View all customer vehicles
  - See all uploaded images

### 7. Reports
- ✅ **View reports** (`/admin/reports.php`)
  - View orders by date range
  - See revenue statistics
  - View top customers
  - See monthly order trends

### 8. General Access
- ✅ Access customer-facing pages (dashboard, orders, gallery)
- ✅ View their own profile
- ✅ Update their profile information

---

## ❌ What Staff CANNOT Do

### 1. System Settings (Admin Only)
- ❌ **Cannot access Settings page** (`/admin/settings.php`)
  - Cannot configure email notification settings
  - Cannot enable/disable email notifications
  - Cannot configure email templates
  - Cannot set email sender information

### 2. Diagnostic Scripts (Admin Only)
- ❌ Cannot access diagnostic/utility scripts:
  - `/admin/check_email_table.php`
  - `/admin/check_settings_file.php`
  - `/admin/setup_email_settings.php`

---

## Key Differences: Staff vs Admin

| Feature | Staff | Admin |
|---------|-------|-------|
| View Dashboard | ✅ | ✅ |
| Manage Orders | ✅ | ✅ |
| Manage Customers | ✅ | ✅ |
| Manage Deposits | ✅ | ✅ |
| Manage Quote Requests | ✅ | ✅ |
| Upload Documents | ✅ | ✅ |
| View Reports | ✅ | ✅ |
| **System Settings** | ❌ | ✅ |
| **Email Configuration** | ❌ | ✅ |

---

## Technical Implementation

### Authentication Checks
- **Staff Access:** `Auth::requireStaff()` - Allows both `'admin'` and `'staff'` roles
- **Admin Only:** `Auth::requireAdmin()` - Allows only `'admin'` role
- **Staff Check:** `Auth::isStaff()` - Returns `true` for both `'admin'` and `'staff'`

### Pages Using `Auth::requireStaff()`
- `/admin/dashboard.php`
- `/admin/orders.php`
- `/admin/orders/create.php`
- `/admin/orders/edit.php`
- `/admin/customers.php`
- `/admin/deposits.php`
- `/admin/deposits/add.php`
- `/admin/deposits/view.php`
- `/admin/quote-requests.php`
- `/admin/quote-requests/view.php`
- `/admin/quote-requests/convert.php`
- `/admin/reports.php`

### Pages Using `Auth::requireAdmin()` (Staff Cannot Access)
- `/admin/settings.php`
- `/admin/check_email_table.php`
- `/admin/check_settings_file.php`
- `/admin/setup_email_settings.php`

### Document Upload Permission
- Uses `Auth::isStaff()` check in `/orders/documents.php`
- Staff can upload and delete documents for any order

---

## Summary

**Staff members have comprehensive access to:**
- All order management functions
- All customer management functions
- All deposit management functions
- All quote request management functions
- Document and image upload/management
- Reports and statistics
- Gallery access

**Staff members are restricted from:**
- System settings configuration
- Email notification settings
- Diagnostic/utility scripts

This design allows staff to handle day-to-day operations while keeping system configuration restricted to administrators only.


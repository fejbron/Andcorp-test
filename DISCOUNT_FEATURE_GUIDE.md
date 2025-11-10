# Discount Feature Implementation Guide

## Overview
The discount feature allows admin staff to apply discounts to orders, providing flexible pricing options for customers. Discounts can be applied as either a fixed amount or a percentage of the order subtotal.

## Features

### Discount Types
1. **No Discount (Default)**: No discount applied to the order
2. **Fixed Amount**: A specific amount in GHS is deducted from the subtotal
3. **Percentage**: A percentage (0-100%) is deducted from the subtotal

### Financial Calculation Flow

```
Subtotal = Vehicle Price + Additional Car Costs + Transportation + Duty + Clearing + Fixing

Discount Amount = 
  - If Fixed: minimum of (discount_value, subtotal)
  - If Percentage: (subtotal × discount_value) / 100
  - If None: 0

Total Cost = Subtotal - Discount Amount
Balance Due = Total Cost - Total Deposits
```

## Database Schema

### New Fields in `orders` Table

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| `discount_type` | ENUM('none', 'fixed', 'percentage') | 'none' | Type of discount applied |
| `discount_value` | DECIMAL(10, 2) | 0.00 | Discount amount or percentage |
| `subtotal` | DECIMAL(10, 2) | 0.00 | Total before discount |

### Migration

Run this SQL to add discount support to existing database:

```bash
mysql -u username -p database_name < database/add_discount_feature_2025.sql
```

##Files Modified

### 1. Database Files
- ✅ `database/schema.sql` - Updated for fresh installations
- ✅ `database/add_discount_feature_2025.sql` - Migration file for existing installations

### 2. Order Management
- ✅ `public/admin/orders/create.php` - Added discount UI during order creation
- ✅ `public/admin/orders/edit.php` - Added discount UI and calculation logic for editing
- ✅ `public/orders/view.php` - Display discount in customer view

### 3. Backend Logic
- ✅ Discount calculation in order update process
- ✅ JavaScript real-time calculation
- ✅ Validation (percentage 0-100, fixed cannot exceed subtotal)

## Usage

### Applying a Discount (Admin)

**Option 1: During Order Creation**

1. **Navigate to Create Order Page**
   - Go to Admin Dashboard → Orders → Create New Order

2. **Fill Order Details**
   - Enter customer information, vehicle details, and purchase price

3. **Scroll to Discount Section**
   - Located in the "Purchase & Financial Information" section

4. **Select Discount Type**
   - Choose from: No Discount, Fixed Amount, or Percentage

5. **Enter Discount Value**
   - For Fixed: Enter amount in GHS (e.g., 500.00)
   - For Percentage: Enter percentage (e.g., 10 for 10% off)

6. **Review Calculation**
   - Subtotal, discount amount, and final total are displayed in real-time
   - Verify the amounts before saving

7. **Create Order**
   - Click "Create Order" to save with discount applied

**Option 2: Edit Existing Order**

1. **Navigate to Order Edit Page**
   - Go to Admin Dashboard → Orders → Edit Order

2. **Scroll to Discount Section**
   - Located after the cost breakdown section

3. **Select Discount Type**
   - Choose from: No Discount, Fixed Amount, or Percentage

4. **Enter Discount Value**
   - For Fixed: Enter amount in GHS (e.g., 500.00)
   - For Percentage: Enter percentage (e.g., 10 for 10% off)

5. **Review Calculation**
   - Subtotal, discount amount, and final total are displayed in real-time
   - Verify the amounts before saving

6. **Save Order**
   - Click "Update Order" to save changes
   - Discount will be reflected immediately

### Viewing Discount (Customer)

Customers can see discount information in:
- Order Details Page
- Financial Summary section
- Shows subtotal, discount (with type/percentage), and final total

## Examples

### Example 1: Fixed Discount

**Scenario**: Give customer GHS 1,000 off their order

**Settings**:
- Discount Type: Fixed Amount (GHS)
- Discount Value: 1000.00

**Calculation**:
```
Subtotal: GHS 10,000.00
Discount: - GHS 1,000.00
Total Cost: GHS 9,000.00
```

### Example 2: Percentage Discount

**Scenario**: Give customer 15% off their order

**Settings**:
- Discount Type: Percentage (%)
- Discount Value: 15

**Calculation**:
```
Subtotal: GHS 10,000.00
Discount (15%): - GHS 1,500.00
Total Cost: GHS 8,500.00
```

### Example 3: No Discount

**Settings**:
- Discount Type: No Discount

**Calculation**:
```
Subtotal: GHS 10,000.00
Discount: GHS 0.00
Total Cost: GHS 10,000.00
```

## Validation Rules

### Fixed Amount Discount
- ✅ Must be >= 0
- ✅ Cannot exceed subtotal
- ✅ Automatically capped at subtotal if entered value is higher

### Percentage Discount
- ✅ Must be between 0 and 100
- ✅ Automatically clamped to 0-100 range
- ✅ Calculated as: (subtotal × percentage) / 100

### General Rules
- ✅ Discount cannot make total cost negative
- ✅ Total cost is always: max(0, subtotal - discount)
- ✅ Changes to cost breakdown automatically recalculate discount
- ✅ Changing discount type resets discount value to 0

## User Interface

### Admin Order Edit Page

**Discount Section Location**: After cost breakdown, before vehicle information

**Fields**:
1. **Discount Type** (Dropdown)
   - No Discount
   - Fixed Amount (GHS)
   - Percentage (%)

2. **Discount Value** (Number Input)
   - Dynamic help text based on selected type
   - Input validation
   - Real-time calculation

**Cost Summary Display**:
```
Subtotal:        GHS 10,000.00
Discount:      - GHS  1,500.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Cost:      GHS  8,500.00
```

### Customer Order View

**Financial Summary**: Shows discount with icon and clear labeling

```
Subtotal:           GHS 10,000.00
💰 Discount (15%): - GHS  1,500.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Total Cost:         GHS  8,500.00
Deposit Paid:       GHS  5,000.00
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Balance Due:        GHS  3,500.00
```

## JavaScript Functionality

### Real-Time Calculation
- Automatically calculates when any cost field changes
- Automatically recalculates when discount type or value changes
- Updates all display fields simultaneously

### Functions
- `calculateTotalUsd()` - Main calculation function
- `updateDiscountHelp()` - Updates help text based on discount type

### Event Listeners
- All cost breakdown fields trigger calculation
- Discount type dropdown triggers help text update + calculation
- Discount value input triggers calculation

## Backward Compatibility

### Existing Orders
- Orders without discount fields will default to:
  - `discount_type`: 'none'
  - `discount_value`: 0.00
  - `subtotal`: Same as `total_cost`

### Migration Safety
- Migration script sets subtotal = total_cost for existing orders
- No data loss - all existing orders work without modification
- Discount display only shows when discount_type != 'none'

## Testing Checklist

After deployment, verify:

**Order Creation:**
- [ ] Create new order without discount
- [ ] Create new order with fixed discount
- [ ] Create new order with percentage discount
- [ ] Verify real-time calculation during order creation
- [ ] Verify discount is saved with new order

**Order Editing:**
- [ ] Edit existing order and add fixed discount
- [ ] Edit existing order and add percentage discount
- [ ] Change discount from fixed to percentage
- [ ] Remove discount (set to "No Discount")
- [ ] Verify discount persists after editing

**Validation:**
- [ ] Verify fixed discount cannot exceed subtotal
- [ ] Verify percentage stays within 0-100
- [ ] Test negative values are rejected
- [ ] Test edge cases (0%, 100%, etc.)

**Display & Calculation:**
- [ ] Check financial summary displays correctly on edit page
- [ ] Check financial summary displays correctly on customer view
- [ ] Verify customer view shows discount with correct type
- [ ] Verify discount shows in sidebar on edit page
- [ ] Test balance_due calculation with discount
- [ ] Verify deposits still update correctly

**Backward Compatibility:**
- [ ] Confirm existing orders (without discount) still work
- [ ] Verify migration sets correct defaults for old orders
- [ ] Test that old orders can be edited and discount can be added

## API/Integration Notes

### Order Data Structure

```php
$order = [
    'subtotal' => 10000.00,           // Sum of all costs before discount
    'discount_type' => 'percentage',   // 'none', 'fixed', or 'percentage'
    'discount_value' => 15.00,         // Amount or percentage value
    'total_cost' => 8500.00,           // After discount
    'total_deposits' => 5000.00,       // Sum of verified deposits
    'balance_due' => 3500.00           // total_cost - total_deposits
];
```

### Discount Calculation Function

```php
// Backend PHP calculation
$subtotal = $vehicle_price + $car_cost + $transportation + $duty + $clearing + $fixing;

$discountAmount = 0;
if ($discountType === 'fixed') {
    $discountAmount = min($discountValue, $subtotal);
} elseif ($discountType === 'percentage') {
    $discountValue = min(max($discountValue, 0), 100);
    $discountAmount = ($subtotal * $discountValue) / 100;
}

$totalCost = max(0, $subtotal - $discountAmount);
$balanceDue = $totalCost - $totalDeposits;
```

## Support

For questions or issues:
- **Technical Support**: info@andcorpautos.com
- **Phone**: +233 24 949 4091

## Notes

- ✅ Discount feature is optional - orders work fine without it
- ✅ Only admin/staff can apply discounts - customers cannot
- ✅ Discount is permanently applied to order (not temporary)
- ✅ Changing costs automatically recalculates discount amount
- ✅ Financial summary always shows accurate calculations
- ✅ All currency amounts in GHS (Ghana Cedis)

---

**Version**: 1.0  
**Date**: November 2025  
**Status**: ✅ Ready for Production


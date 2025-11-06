# Debugging Quote Request View Redirect

## Problem
Visiting `https://app.andcorpautos.com/public/admin/quote-requests/view.php` redirects to the listing page.

## Possible Causes

### 1. Missing ID Parameter
The page requires an ID: `view.php?id=5`

**Test:** Visit with an ID:
```
https://app.andcorpautos.com/public/admin/quote-requests/view.php?id=2
```

### 2. Quote Request Doesn't Exist
The ID you're using doesn't exist in the database.

**Check:** Run the debug script to see all existing IDs:
```
https://app.andcorpautos.com/public/admin/debug_quote_ids.php
```

### 3. Database Query Failing
The `findById()` method is failing even though the record exists.

## Step-by-Step Debugging

### Step 1: Check Server Error Logs
Look for these log entries:
```
Quote Request View - Raw ID from GET: ...
Quote Request View - Sanitized ID: ...
Quote Request View - Attempting to find quote request with ID: ...
QuoteRequest::findById() - Quote request ID X does not exist in database
```

### Step 2: Run Debug Scripts

**List all quote requests:**
```bash
https://app.andcorpautos.com/public/admin/debug_quote_ids.php
```

**Test specific ID:**
```bash
https://app.andcorpautos.com/public/admin/debug_quote_ids.php?id=2
```

**Full debug for view page:**
```bash
https://app.andcorpautos.com/public/admin/debug_quote_view.php?id=2
```

### Step 3: Check Quote Request IDs in Database

Run this SQL query in phpMyAdmin:
```sql
SELECT id, request_number, status, customer_id, created_at 
FROM quote_requests 
ORDER BY id DESC 
LIMIT 20;
```

### Step 4: Test the View Page

Use an ID from Step 3:
```
https://app.andcorpautos.com/public/admin/quote-requests/view.php?id=ACTUAL_ID
```

Replace `ACTUAL_ID` with a real ID from your database.

## What the Logs Will Tell You

The error logs will show:
- ✅ **Raw ID from GET**: What ID was in the URL
- ✅ **Sanitized ID**: What ID after validation
- ✅ **findById() execution**: Whether the database query ran
- ✅ **Existing IDs**: List of all quote request IDs in the database
- ✅ **Error messages**: Any database or validation errors

## Expected Behavior

**If ID is missing:**
```
Quote Request View - Invalid or missing ID. Raw: NULL, Sanitized: 0
```

**If ID doesn't exist:**
```
Quote Request View - Quote request not found for ID: 5
Quote Request View - Existing quote request IDs: Array([0] => Array([id] => 2, ...))
```

**If successful:**
```
Quote Request View - Successfully found quote request ID: 2, Request Number: QR-20250106-1234
```

## Quick Fix

If you don't have any quote requests yet:
1. Go to: `https://app.andcorpautos.com/public/quotes/request.php`
2. Submit a test quote request as a customer
3. Then view it as admin

## Report Back

Share the output from:
1. The debug script: `debug_quote_ids.php`
2. The server error log entries starting with "Quote Request View -"
3. The exact URL you're trying to access (including the ID parameter)


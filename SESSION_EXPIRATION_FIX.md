# Session Expiration 404 Fix

## Problem
When users are inactive for a while and reload the page on the live cPanel server, they get a 404 error instead of being redirected to the login page.

## Root Cause
The issue was caused by incorrect path detection on cPanel servers when sessions expire. The `getBasePath()` function wasn't handling cPanel's server environment variables correctly, leading to malformed redirect URLs.

## Solution

### 1. Improved Path Detection (`getBasePath()`)
- Added multiple fallback methods to detect the base path:
  - Method 1: `SCRIPT_NAME` (primary method)
  - Method 2: `REQUEST_URI` (better for cPanel)
  - Method 3: `DOCUMENT_ROOT` + `SCRIPT_FILENAME` (cPanel fallback)
  - Method 4: File system path detection (final fallback)

### 2. Enhanced URL Generation (`url()`)
- Now returns absolute URLs (with `http://` or `https://`) when `HTTP_HOST` is available
- This ensures URLs work correctly on cPanel servers regardless of path configuration

### 3. Improved Redirect Function (`redirect()`)
- Uses absolute URLs when possible (prevents 404 errors)
- Better handling of both relative and absolute URLs
- Automatically detects protocol (HTTP/HTTPS)

### 4. Session Expiration Handling
- When a session expires and user tries to access a protected page:
  - The intended destination URL is stored in the session
  - User is redirected to login page
  - After successful login, user is redirected back to their intended destination
  - If no destination was stored, user goes to their default dashboard

## Files Modified

1. **`public/bootstrap.php`**
   - Enhanced `getBasePath()` with multiple fallback methods
   - Improved `redirect()` to use absolute URLs
   - Updated `url()` to return absolute URLs when possible

2. **`app/Auth.php`**
   - Updated `requireAuth()` to store intended destination as absolute URL
   - Ensures redirect after login works correctly

3. **`public/login.php`**
   - Added logic to redirect users back to their intended destination after login
   - Falls back to default dashboard if no destination was stored

## Testing

### On Live Server:
1. Log in to the application
2. Wait for session to expire (1 hour of inactivity) OR manually clear session
3. Try to access a protected page (e.g., `/admin/dashboard.php`)
4. You should be redirected to the login page (not get a 404)
5. After logging in, you should be redirected back to the page you were trying to access

### Diagnostic Tool:
A diagnostic script has been created at `public/admin/debug_paths.php` to help troubleshoot path issues on the live server. This script shows:
- All server variables
- Calculated base path
- Generated URLs
- Session information

**Note:** Delete `public/admin/debug_paths.php` after debugging for security.

## Additional Notes

- Session lifetime is set to 1 hour (`session.gc_maxlifetime = 3600`)
- Session cookie lifetime is set to "until browser closes" (`session.cookie_lifetime = 0`)
- The fix ensures that even if path detection fails, absolute URLs will still work correctly
- All redirects now use absolute URLs when possible, which is more reliable on cPanel servers

## Deployment

1. Upload the modified files to your live server:
   - `public/bootstrap.php`
   - `app/Auth.php`
   - `public/login.php`

2. Test the fix by:
   - Logging in
   - Waiting for session to expire or clearing session manually
   - Trying to access a protected page
   - Verifying you're redirected to login (not 404)
   - Logging in and verifying you're redirected back

3. (Optional) Use the diagnostic tool to verify path detection:
   - Access `https://yourdomain.com/public/admin/debug_paths.php` (while logged in as admin)
   - Review the output to ensure paths are being calculated correctly
   - Delete the file after verification


# Deployment Guide for Namecheap Shared Hosting

This guide will help you deploy the Andcorp Autos application to Namecheap shared hosting (cPanel).

## Prerequisites

- Access to Namecheap cPanel
- FTP/SFTP access or cPanel File Manager
- Database credentials (MySQL database)
- Domain name configured (e.g., app.andcorpautos.com)

## Step 1: Prepare Database

1. **Create Database in cPanel**
   - Log into cPanel
   - Go to "MySQL Databases"
   - Create a new database (e.g., `cpses_username_andcorp`)
   - Create a database user and assign full privileges
   - Note down: database name, username, password, and host (usually `localhost`)

2. **Import Database Schema**
   - In cPanel, go to "phpMyAdmin"
   - Select your database
   - Click "Import" tab
   - Upload and import `database/schema.sql`
   - Verify all tables are created

3. **Run Additional Migrations** (if needed)
   - Import `database/email_notification_settings.sql` if not in schema.sql
   - Run `database/add_evidence_of_delivery.sql` to add document type
   - Run `database/fix_status_enum.sql` if status values need updating

## Step 2: Configure Database Connection

1. **Edit Database Config**
   - Locate `config/database.php` in your local files
   - Update with your production credentials:
   ```php
   'host' => 'localhost',
   'dbname' => 'your_database_name',
   'username' => 'your_database_user',
   'password' => 'your_database_password',
   ```

2. **Update File After Upload**
   - After uploading files, edit `config/database.php` directly in cPanel File Manager
   - Or use SFTP to edit the file

## Step 3: Upload Files

### Option A: Using cPanel File Manager

1. **Compress Files Locally**
   - Create a ZIP file of the entire project (excluding node_modules, .git, etc.)
   - Or zip specific folders

2. **Upload to cPanel**
   - Log into cPanel
   - Go to "File Manager"
   - Navigate to `public_html` or your domain's root directory
   - Upload the ZIP file
   - Extract the ZIP file
   - Move files to the correct location (see File Structure below)

### Option B: Using FTP/SFTP

1. **Connect via FTP/SFTP Client**
   - Use FileZilla, WinSCP, or similar
   - Connect using FTP/SFTP credentials from cPanel

2. **Upload Files**
   - Upload all files maintaining the directory structure
   - Ensure file permissions are set correctly (see Step 4)

## Step 4: File Structure on Server

Your files should be structured like this on the server:

```
public_html/                    (or your domain root)
├── Andcorp-test/              (or your project folder name)
│   ├── app/
│   │   ├── Auth.php
│   │   ├── Database.php
│   │   ├── Notification.php
│   │   ├── Security.php
│   │   ├── Validator.php
│   │   └── Models/
│   ├── config/
│   │   └── database.php       (UPDATE WITH PRODUCTION CREDENTIALS)
│   ├── database/
│   │   └── *.sql              (migration files)
│   ├── public/                (THIS IS YOUR DOCUMENT ROOT)
│   │   ├── .htaccess
│   │   ├── bootstrap.php
│   │   ├── index.php
│   │   ├── login.php
│   │   ├── admin/
│   │   ├── assets/
│   │   ├── includes/
│   │   └── orders/
│   └── uploads/               (create this folder)
│       ├── cars/
│       ├── documents/
│       └── deposit_slips/
```

### Important: Document Root Configuration

**Option 1: Point Document Root to `public` folder (RECOMMENDED)**
- In cPanel, go to "Subdomains" or "Addon Domains"
- Edit your domain/subdomain
- Set document root to: `/home/username/public_html/Andcorp-test/public`
- This allows URLs like: `https://app.andcorpautos.com/`

**Option 2: Use `public` in URL**
- Keep document root at project root
- URLs will be: `https://app.andcorpautos.com/public/`
- No configuration changes needed

## Step 5: Set File Permissions

Set correct file and folder permissions:

```bash
# Folders (755)
chmod 755 app/
chmod 755 config/
chmod 755 public/
chmod 755 public/admin/
chmod 755 uploads/
chmod 755 uploads/cars/
chmod 755 uploads/documents/
chmod 755 uploads/deposit_slips/

# Files (644)
chmod 644 config/database.php
chmod 644 public/.htaccess
chmod 644 public/bootstrap.php
```

### Using cPanel File Manager:
- Right-click folders → Change Permissions → 755
- Right-click files → Change Permissions → 644

## Step 6: Create Upload Directories

Create and set permissions for upload directories:

```bash
mkdir -p uploads/cars
mkdir -p uploads/documents
mkdir -p uploads/deposit_slips
chmod 755 uploads/cars
chmod 755 uploads/documents
chmod 755 uploads/deposit_slips
```

Or use cPanel File Manager to create these folders.

## Step 7: Configure .htaccess

Ensure `.htaccess` files are uploaded:

1. **`public/.htaccess`** - Should be present for URL rewriting
2. Check that mod_rewrite is enabled (usually enabled by default on Namecheap)

## Step 8: Environment Configuration

1. **Update Base Path Detection**
   - The `getBasePath()` function in `public/bootstrap.php` should auto-detect paths
   - If URLs are incorrect, you may need to manually set base path (see troubleshooting)

2. **Email Configuration**
   - Update email settings in Admin Panel → Settings
   - Configure SMTP settings if using external email service
   - Default uses PHP `mail()` function

## Step 9: Test Deployment

1. **Access Application**
   - Visit: `https://app.andcorpautos.com/` (or your configured URL)
   - Test login functionality

2. **Verify Features**
   - [ ] Login works
   - [ ] Dashboard loads
   - [ ] Quote requests page loads
   - [ ] Order management works
   - [ ] File uploads work
   - [ ] Email notifications work (test with admin settings)

3. **Check Error Logs**
   - In cPanel, go to "Error Log"
   - Check for any PHP errors
   - Fix any issues found

## Step 10: Security Checklist

- [ ] Change default admin password
- [ ] Update `config/database.php` with strong credentials
- [ ] Set proper file permissions (755 for folders, 644 for files)
- [ ] Remove any test/debug files (`test_*.php`, `debug_*.php`)
- [ ] Ensure `.htaccess` is protecting sensitive files
- [ ] Enable HTTPS/SSL certificate
- [ ] Review and update email notification settings

## Troubleshooting

### Issue: 404 Not Found
**Solution:**
- Check document root is pointing to `public` folder
- Verify `.htaccess` file exists in `public` folder
- Check mod_rewrite is enabled

### Issue: Database Connection Error
**Solution:**
- Verify database credentials in `config/database.php`
- Check database host (may be `localhost` or `127.0.0.1`)
- Ensure database user has proper permissions
- Check database name includes full cPanel username prefix

### Issue: File Upload Not Working
**Solution:**
- Check `uploads/` folder exists and has write permissions (755)
- Verify PHP `upload_max_filesize` and `post_max_size` settings
- Check folder permissions: `chmod 755 uploads/`

### Issue: URLs Not Generating Correctly
**Solution:**
- Run diagnostic: `https://yourdomain.com/public/admin/debug_url_generation.php`
- Check `getBasePath()` function in `public/bootstrap.php`
- Verify `REQUEST_URI` and `SCRIPT_NAME` server variables

### Issue: Session Not Working
**Solution:**
- Check `session.save_path` in PHP settings
- Verify `uploads/` folder is writable (sessions may be stored there)
- Clear browser cookies and try again

### Issue: Email Not Sending
**Solution:**
- Check email settings in Admin Panel → Settings
- Verify SMTP credentials if using external service
- Check PHP `mail()` function is enabled
- Review error logs for email-related errors

## Post-Deployment

1. **Remove Debug Files** (if any):
   ```bash
   rm -f public/admin/debug_*.php
   rm -f public/admin/test_*.php
   rm -f public/*_test.php
   ```

2. **Set Up Regular Backups**:
   - Use cPanel Backup feature
   - Schedule regular database backups
   - Backup uploads folder regularly

3. **Monitor Error Logs**:
   - Regularly check cPanel Error Log
   - Set up error notifications if possible

4. **Performance Optimization**:
   - Enable PHP opcode caching (OPcache)
   - Optimize database queries
   - Compress images in uploads

## Support

For issues specific to Namecheap hosting:
- Namecheap Support: https://www.namecheap.com/support/
- cPanel Documentation: https://docs.cpanel.net/

For application-specific issues:
- Check error logs in cPanel
- Review `DEPLOYMENT.md` troubleshooting section
- Contact development team with error details

## File Upload Checklist

Before uploading, ensure you have:

- [ ] Updated `config/database.php` with production credentials
- [ ] Removed any local development files (test_*.php, debug_*.php)
- [ ] Verified all required folders exist
- [ ] Set correct file permissions
- [ ] Created upload directories
- [ ] Exported database schema
- [ ] Backup of current production (if updating)

## Quick Deploy Script

See `deploy.sh` for an automated deployment script (requires SSH access).

---

**Last Updated:** 2025-01-XX
**Version:** 1.0


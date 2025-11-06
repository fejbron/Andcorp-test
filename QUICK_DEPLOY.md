# Quick Deployment Guide for Namecheap

## Quick Steps

### 1. Prepare Deployment Package
```bash
./deploy.sh
```

This creates a `deploy_package/` directory with all files ready for upload.

### 2. Update Database Configuration

Edit `deploy_package/config/database.php` with your production credentials:
```php
'host' => 'localhost',
'dbname' => 'your_cpanel_db_name',      // e.g., 'cpses_username_andcorp'
'username' => 'your_cpanel_db_user',    // e.g., 'cpses_username_dbuser'
'password' => 'your_db_password',
```

### 3. Upload to Server

**Option A: Using cPanel File Manager**
1. Log into cPanel
2. Go to File Manager
3. Navigate to `public_html` or your domain root
4. Upload `Andcorp-test_deploy.zip`
5. Extract the ZIP file

**Option B: Using FTP/SFTP**
1. Connect using FTP credentials from cPanel
2. Upload all files from `deploy_package/` directory
3. Maintain directory structure

### 4. Set File Permissions

In cPanel File Manager or via SSH:
```bash
# Folders
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Upload directories (make writable)
chmod 755 uploads/
chmod 755 uploads/cars/
chmod 755 uploads/documents/
chmod 755 uploads/deposit_slips/
```

### 5. Create Database

1. In cPanel, go to "MySQL Databases"
2. Create database (e.g., `andcorp_db`)
3. Create database user
4. Assign user to database with ALL PRIVILEGES
5. Note down credentials

### 6. Import Database

1. In cPanel, go to "phpMyAdmin"
2. Select your database
3. Click "Import"
4. Upload `database/schema.sql`
5. Click "Go"

### 7. Run Additional Migrations

In phpMyAdmin, run these SQL files if needed:
- `database/email_notification_settings.sql`
- `database/add_evidence_of_delivery.sql`
- `database/fix_status_enum.sql` (only if needed)

### 8. Configure Document Root (Recommended)

**Option 1: Point Document Root to `public` folder**
1. In cPanel, go to "Subdomains" or "Addon Domains"
2. Edit your domain
3. Set document root to: `/home/username/public_html/Andcorp-test/public`
4. URLs will be: `https://app.andcorpautos.com/`

**Option 2: Keep as is**
- URLs will be: `https://app.andcorpautos.com/public/`
- No configuration needed

### 9. Test Application

1. Visit your domain
2. Test login
3. Check all pages load
4. Test file uploads
5. Check error logs in cPanel

### 10. Post-Deployment

1. Remove debug files (if any):
   ```bash
   rm -f public/admin/debug_*.php
   rm -f public/admin/check_*.php
   ```

2. Set up backups in cPanel

3. Configure email settings in Admin Panel → Settings

## Troubleshooting

### URLs Not Working
- Check `.htaccess` file exists in `public/` folder
- Verify mod_rewrite is enabled
- Check document root configuration

### Database Connection Error
- Verify credentials in `config/database.php`
- Check database host (usually `localhost`)
- Ensure database user has proper permissions

### File Upload Not Working
- Check `uploads/` folders exist and have 755 permissions
- Verify PHP upload limits in cPanel

### 404 Errors
- Verify document root points to `public/` folder
- Check `.htaccess` file is present
- Review error logs in cPanel

## Important Files

- `config/database.php` - Database configuration (UPDATE THIS!)
- `public/.htaccess` - URL rewriting and security
- `database/schema.sql` - Database schema
- `DEPLOYMENT.md` - Full deployment guide
- `PRODUCTION_CHECKLIST.md` - Deployment checklist

## Support

For detailed instructions, see `DEPLOYMENT.md`
For troubleshooting, check cPanel Error Log

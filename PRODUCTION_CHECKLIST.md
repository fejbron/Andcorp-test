# Production Deployment Checklist

Use this checklist to ensure everything is ready for production deployment.

## Pre-Deployment

### Files Preparation
- [ ] Run `./deploy.sh` to create deployment package
- [ ] Review files in `deploy_package/` directory
- [ ] Remove any test/debug files
- [ ] Verify all required files are included

### Database
- [ ] Database created in cPanel
- [ ] Database user created with proper permissions
- [ ] Database credentials documented securely
- [ ] `database/schema.sql` ready for import
- [ ] Migration files ready:
  - [ ] `database/email_notification_settings.sql`
  - [ ] `database/add_evidence_of_delivery.sql`
  - [ ] `database/fix_status_enum.sql` (if needed)

### Configuration
- [ ] Update `config/database.php` with production credentials
- [ ] Verify email settings (Admin Panel → Settings)
- [ ] Review security settings
- [ ] Check file upload limits

### Server Requirements
- [ ] PHP 7.4 or higher (check: `php -v`)
- [ ] MySQL 5.7 or higher
- [ ] mod_rewrite enabled
- [ ] Sufficient disk space
- [ ] Sufficient memory limits

## Deployment

### Upload Files
- [ ] Upload deployment package to server
- [ ] Extract files to correct location
- [ ] Verify file structure is correct

### Database Setup
- [ ] Import `database/schema.sql`
- [ ] Run additional migrations
- [ ] Verify all tables created
- [ ] Test database connection

### File Permissions
- [ ] Set folder permissions to 755
- [ ] Set file permissions to 644
- [ ] Set `uploads/` folders to 755
- [ ] Verify `config/database.php` permissions (644)

### Directory Structure
- [ ] Create `uploads/cars/` directory
- [ ] Create `uploads/documents/` directory
- [ ] Create `uploads/deposit_slips/` directory
- [ ] Set proper permissions on upload directories

### Document Root
- [ ] Configure document root to `public/` folder (recommended)
- [ ] OR verify URLs include `/public/` path
- [ ] Test base URL loads correctly

### .htaccess
- [ ] Verify `public/.htaccess` is uploaded
- [ ] Test URL rewriting works
- [ ] Verify mod_rewrite is enabled

## Post-Deployment Testing

### Basic Functionality
- [ ] Homepage loads
- [ ] Login page loads
- [ ] Can log in with admin credentials
- [ ] Dashboard loads
- [ ] Navigation works

### Admin Features
- [ ] Quote requests page loads
- [ ] Can view individual quote request
- [ ] Orders page loads
- [ ] Deposits page loads
- [ ] Customers page loads
- [ ] Settings page loads

### User Features
- [ ] Customer can register/login
- [ ] Customer dashboard loads
- [ ] Order creation works
- [ ] Document upload works
- [ ] File uploads save correctly

### Critical Functions
- [ ] File uploads work (cars, documents, deposit slips)
- [ ] Database queries execute correctly
- [ ] Email notifications send (test)
- [ ] Session management works
- [ ] CSRF protection works

### Error Handling
- [ ] Check cPanel Error Log for errors
- [ ] Test error pages (404, 500)
- [ ] Verify error messages are user-friendly
- [ ] No PHP warnings/notices in production

## Security

### Credentials
- [ ] Changed default admin password
- [ ] Database credentials are secure
- [ ] No credentials in version control
- [ ] `.htaccess` protects sensitive files

### File Permissions
- [ ] Config files not world-readable
- [ ] Upload directories have correct permissions
- [ ] No executable files in web root (except PHP)

### Application Security
- [ ] CSRF protection enabled
- [ ] SQL injection protection (prepared statements)
- [ ] XSS protection (htmlspecialchars)
- [ ] File upload validation
- [ ] Session security configured

### SSL/HTTPS
- [ ] SSL certificate installed
- [ ] HTTPS redirects configured
- [ ] All pages load over HTTPS
- [ ] Mixed content warnings resolved

## Performance

### Optimization
- [ ] Images optimized
- [ ] CSS/JS minified (if applicable)
- [ ] Database indexes in place
- [ ] Caching enabled (if applicable)

### Monitoring
- [ ] Error logging configured
- [ ] Access logs monitored
- [ ] Performance monitoring set up
- [ ] Backup schedule configured

## Documentation

### For Client
- [ ] Admin user guide provided
- [ ] Support contact information updated
- [ ] Email settings documented

### For Maintenance
- [ ] Deployment process documented
- [ ] Database backup process documented
- [ ] Troubleshooting guide provided
- [ ] Contact information for issues

## Final Steps

### Cleanup
- [ ] Remove debug files from server
- [ ] Remove test data (if any)
- [ ] Clean up temporary files
- [ ] Remove deployment scripts if not needed

### Backup
- [ ] Full backup of deployed files
- [ ] Database backup created
- [ ] Backup stored securely
- [ ] Backup restoration tested

### Go Live
- [ ] DNS configured correctly
- [ ] Domain points to correct server
- [ ] All tests passed
- [ ] Client notified of deployment
- [ ] Monitoring active

## Post-Launch Monitoring

### First 24 Hours
- [ ] Monitor error logs hourly
- [ ] Check for any user-reported issues
- [ ] Verify all critical features work
- [ ] Monitor server resources

### First Week
- [ ] Daily error log review
- [ ] Performance monitoring
- [ ] User feedback collection
- [ ] Address any issues promptly

## Rollback Plan

If issues occur:
- [ ] Backup current production
- [ ] Restore previous version
- [ ] Restore database backup
- [ ] Verify rollback successful
- [ ] Document issues encountered

---

**Deployment Date:** _______________
**Deployed By:** _______________
**Version:** _______________
**Notes:** _______________

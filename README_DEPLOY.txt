================================================================================
ANDCORP AUTOS - DEPLOYMENT PACKAGE
================================================================================

This package contains everything needed to deploy the Andcorp Autos application
to Namecheap shared hosting (cPanel).

QUICK START:
1. Update config/database.php with your production database credentials
2. Upload all files to your server (maintaining directory structure)
3. Set file permissions: folders (755), files (644)
4. Create database in cPanel and import database/schema.sql
5. Configure document root to point to public/ folder (recommended)
6. Test the application

IMPORTANT FILES:
- config/database.php          → UPDATE WITH PRODUCTION CREDENTIALS
- database/schema.sql          → Import this to create database tables
- public/.htaccess             → URL rewriting and security
- DEPLOYMENT.md                → Full deployment guide
- QUICK_DEPLOY.md              → Quick reference guide
- PRODUCTION_CHECKLIST.md      → Deployment checklist

DIRECTORY STRUCTURE:
app/                    → Application core files
config/                 → Configuration files (UPDATE database.php!)
database/               → SQL migration files
public/                 → Web-accessible files (DOCUMENT ROOT)
uploads/                → User uploads (cars, documents, deposit slips)

REQUIREMENTS:
- PHP 7.4 or higher
- MySQL 5.7 or higher
- mod_rewrite enabled
- 50MB+ disk space

SUPPORT:
- Full guide: DEPLOYMENT.md
- Quick guide: QUICK_DEPLOY.md
- Checklist: PRODUCTION_CHECKLIST.md

================================================================================

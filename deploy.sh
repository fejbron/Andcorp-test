#!/bin/bash

# Deployment Script for Namecheap Shared Hosting
# This script helps prepare and deploy the application

set -e

echo "========================================="
echo "Andcorp Autos Deployment Script"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="Andcorp-test"
DEPLOY_DIR="deploy_package"

# Step 1: Create deployment directory
echo -e "${YELLOW}Step 1: Creating deployment package...${NC}"
rm -rf $DEPLOY_DIR
mkdir -p $DEPLOY_DIR

# Step 2: Copy necessary files
echo -e "${YELLOW}Step 2: Copying files...${NC}"

# Copy app directory
echo "  - Copying app/ directory..."
cp -r app $DEPLOY_DIR/

# Copy config directory
echo "  - Copying config/ directory..."
cp -r config $DEPLOY_DIR/

# Copy public directory
echo "  - Copying public/ directory..."
cp -r public $DEPLOY_DIR/

# Copy database directory
echo "  - Copying database/ directory..."
cp -r database $DEPLOY_DIR/

# Copy .htaccess if exists
if [ -f ".htaccess" ]; then
    echo "  - Copying .htaccess..."
    cp .htaccess $DEPLOY_DIR/
fi

# Copy public/.htaccess
if [ -f "public/.htaccess" ]; then
    echo "  - Copying public/.htaccess..."
    cp public/.htaccess $DEPLOY_DIR/public/
fi

# Step 3: Create upload directories
echo -e "${YELLOW}Step 3: Creating upload directories...${NC}"
mkdir -p $DEPLOY_DIR/uploads/cars
mkdir -p $DEPLOY_DIR/uploads/documents
mkdir -p $DEPLOY_DIR/uploads/deposit_slips

# Create .gitkeep files to preserve empty directories
touch $DEPLOY_DIR/uploads/cars/.gitkeep
touch $DEPLOY_DIR/uploads/documents/.gitkeep
touch $DEPLOY_DIR/uploads/deposit_slips/.gitkeep

# Step 4: Remove development files
echo -e "${YELLOW}Step 4: Removing development files...${NC}"

# Remove test files
find $DEPLOY_DIR -name "test_*.php" -type f -delete 2>/dev/null || true
find $DEPLOY_DIR -name "*_test.php" -type f -delete 2>/dev/null || true
find $DEPLOY_DIR -name "debug_*.php" -type f -delete 2>/dev/null || true
find $DEPLOY_DIR -name "check_*.php" -type f -delete 2>/dev/null || true
find $DEPLOY_DIR -name "production_readiness_test.php" -type f -delete 2>/dev/null || true

# Remove specific debug files
rm -f $DEPLOY_DIR/public/admin/debug_*.php 2>/dev/null || true
rm -f $DEPLOY_DIR/public/admin/check_*.php 2>/dev/null || true
rm -f $DEPLOY_DIR/public/admin/debug_url_generation.php 2>/dev/null || true
rm -f $DEPLOY_DIR/public/admin/debug_quote_view.php 2>/dev/null || true
rm -f $DEPLOY_DIR/public/admin/debug_customers.php 2>/dev/null || true
rm -f $DEPLOY_DIR/public/admin/debug_paths.php 2>/dev/null || true
rm -f $DEPLOY_DIR/test_url.php 2>/dev/null || true

# Remove .git directories if any
find $DEPLOY_DIR -name ".git" -type d -exec rm -rf {} + 2>/dev/null || true

# Remove node_modules if any
find $DEPLOY_DIR -name "node_modules" -type d -exec rm -rf {} + 2>/dev/null || true

# Remove documentation files (keep DEPLOYMENT.md and PRODUCTION_CHECKLIST.md)
find $DEPLOY_DIR -name "*.md" -type f ! -name "DEPLOYMENT.md" ! -name "PRODUCTION_CHECKLIST.md" -delete 2>/dev/null || true

# Step 5: Set file permissions (simulate)
echo -e "${YELLOW}Step 5: File permissions will need to be set on server:${NC}"
echo "  - Folders: chmod 755"
echo "  - Files: chmod 644"
echo "  - config/database.php: chmod 644 (update with production credentials)"

# Step 6: Create deployment info file
echo -e "${YELLOW}Step 6: Creating deployment info...${NC}"
cat > $DEPLOY_DIR/DEPLOYMENT_INFO.txt << EOF
Andcorp Autos - Deployment Package
Generated: $(date)
Version: 1.0

IMPORTANT STEPS AFTER UPLOAD:

1. Update config/database.php with production database credentials
2. Set file permissions:
   - Folders: 755
   - Files: 644
3. Create upload directories if not present:
   - uploads/cars (755)
   - uploads/documents (755)
   - uploads/deposit_slips (755)
4. Import database schema from database/schema.sql
5. Run additional migrations if needed:
   - database/email_notification_settings.sql
   - database/add_evidence_of_delivery.sql
6. Configure document root to point to public/ folder (recommended)
7. Test the application and check error logs

For detailed instructions, see DEPLOYMENT.md
EOF

# Step 7: Create ZIP file
echo -e "${YELLOW}Step 7: Creating ZIP archive...${NC}"
cd $DEPLOY_DIR
zip -r ../${PROJECT_NAME}_deploy.zip . -x "*.git*" -x "*.DS_Store" -x "*node_modules*"
cd ..

echo ""
echo -e "${GREEN}=========================================${NC}"
echo -e "${GREEN}Deployment package created successfully!${NC}"
echo -e "${GREEN}=========================================${NC}"
echo ""
echo "Package location: ${DEPLOY_DIR}/"
echo "ZIP file: ${PROJECT_NAME}_deploy.zip"
echo ""
echo -e "${YELLOW}Next steps:${NC}"
echo "1. Review files in ${DEPLOY_DIR}/"
echo "2. Update config/database.php with production credentials"
echo "3. Upload ${PROJECT_NAME}_deploy.zip to your server"
echo "4. Extract on server and follow DEPLOYMENT_INFO.txt"
echo ""
echo -e "${RED}IMPORTANT: Update database credentials before uploading!${NC}"


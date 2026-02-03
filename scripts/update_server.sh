#!/bin/bash

# Kayarine Backend Update Script
# Usage: ./update_server.sh
# Run this script on your GCP Server to apply the latest changes.

APP_DIR="/home/kayarine_server/kayarine-webhook" # Corrected path
SERVICE_NAME="kayarine-webhook"

echo ">>> Starting Update Process..."

# 1. Check if we are in the right directory or where files are
if [ ! -f "app.py" ] || [ ! -f "sheet_manager.py" ]; then
    echo "❌ Error: app.py or sheet_manager.py not found in current directory."
    echo "   Please upload the new files to this server first."
    exit 1
fi

# 2. Backup old files
echo ">>> Backing up old files..."
cp "$APP_DIR/app.py" "$APP_DIR/app.py.bak_$(date +%F_%T)"
cp "$APP_DIR/sheet_manager.py" "$APP_DIR/sheet_manager.py.bak_$(date +%F_%T)"

# 3. Copy new files
echo ">>> Copying new files to $APP_DIR..."
cp app.py "$APP_DIR/"
cp sheet_manager.py "$APP_DIR/"
cp wati_manager.py "$APP_DIR/"
cp wc_handler.py "$APP_DIR/"
cp campaign_worker.py "$APP_DIR/"
cp config.py "$APP_DIR/"
cp requirements.txt "$APP_DIR/"
cp GUIDELINES.md "$APP_DIR/"
# Copy init script if you want to run it manually later
cp init_settings.py "$APP_DIR/"

# 3.5 Install new dependencies (flask-cors)
echo ">>> Installing new dependencies..."
if [ -d "$APP_DIR/venv" ]; then
    "$APP_DIR/venv/bin/pip" install -r "$APP_DIR/requirements.txt"
else
    # Fallback if no venv is used (system pip)
    pip3 install -r "$APP_DIR/requirements.txt"
fi

# 4. Copy Plugin files for user to download/deploy manually if needed
# Zip the plugin for easy deployment
echo ">>> Zipping WordPress Plugin..."
if [ -d "kayarine-booking" ]; then
    zip -r kayarine-booking.zip kayarine-booking -x "*.DS_Store"
    cp kayarine-booking.zip "$APP_DIR/"
    echo "✅ kayarine-booking.zip updated in $APP_DIR"
fi

# 5. Fix permissions
echo ">>> Setting permissions..."
chown -R www-data:www-data "$APP_DIR"

# 5. Restart Services
echo ">>> Restarting Services ($SERVICE_NAME & kayarine-campaign-worker)..."
sudo systemctl restart $SERVICE_NAME
# Check if worker service exists before restarting
if systemctl list-unit-files | grep -q kayarine-campaign-worker.service; then
    sudo systemctl restart kayarine-campaign-worker
else
    echo "⚠️ Worker service not found. You may need to run deploy_gcp.sh to install it."
fi

# 6. Check Status
echo ">>> Checking Service Status..."
sudo systemctl status $SERVICE_NAME --no-pager
if systemctl list-unit-files | grep -q kayarine-campaign-worker.service; then
    sudo systemctl status kayarine-campaign-worker --no-pager
fi

echo ""
echo "✅ Update Complete!"
echo "If the status is 'active (running)', your backend is ready."

#!/bin/bash
# ==============================================================================
# DuckDNS IP Auto-Updater Script
# ==============================================================================
#
# INSTRUCTIONS:
# 1. Fill in your DuckDNS subdomain name and token.
# 2. Make the script executable: chmod +x duckdns.sh
# 3. Schedule this script using cron to run every 5 minutes:
#    crontab -e
#    Add this line:
#    */5 * * * * /var/www/sistem-penjadwalan/deploy/duckdns.sh >/dev/null 2>&1
#

# --- CONFIGURATION ---
SUBDOMAIN="yourdomain" # e.g. "myawesomeapp" (without .duckdns.org)
TOKEN="your-duckdns-token-goes-here"
LOG_FILE="/var/log/duckdns.log"
# --------------------

# If not running as root and log file isn't writable, fallback to a local log inside the deploy folder
if [ ! -w "$LOG_FILE" ]; then
    # Ensure log file exists or fallback to the local deploy folder
    touch "$LOG_FILE" 2>/dev/null || LOG_FILE="$(dirname "$0")/duckdns.log"
fi

# Run the update curl command
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
RESPONSE=$(curl -s "https://www.duckdns.org/update?domains=$SUBDOMAIN&token=$TOKEN&ip=")

# Log the result
if [ "$RESPONSE" = "OK" ]; then
    echo "[$TIMESTAMP] DuckDNS update OK. IP successfully synced." >> "$LOG_FILE"
elif [ "$RESPONSE" = "KO" ]; then
    echo "[$TIMESTAMP] DuckDNS update KO. Please verify your SUBDOMAIN and TOKEN." >> "$LOG_FILE"
else
    echo "[$TIMESTAMP] DuckDNS update failed. Network error or invalid response: $RESPONSE" >> "$LOG_FILE"
fi

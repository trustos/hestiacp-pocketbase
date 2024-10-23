#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status

BLUE="\e[34m"
GREEN="\e[32m"
RED="\e[31m"
YELLOW="\e[33m"
ENDCOLOR="\e[0m"
START="[${GREEN}hestiacp/${ENDCOLOR}${BLUE}pocketbase${ENDCOLOR}]"

echo -e "${RED}       _   _       _           _        _ _
      | | | |     (_)         | |      | | |
      | | | |_ __  _ _ __  ___| |_ __ _| | |
      | | | | '_ \| | '_ \/ __| __/ _\` | | |
      | |_| | | | | | | | \__ \ || (_| | | |
       \___/|_| |_|_|_| |_|___/\__\__,_|_|_|${ENDCOLOR}"

echo -e "${BLUE}┬ ┬┌─┐┌─┐┌┬┐┬┌─┐┌─┐┌─┐   ┌─┐┌─┐┌─┐┬┌─┌─┐┌┬┐┌┐ ┌─┐┌─┐┌─┐
├─┤├┤ └─┐ │ │├─┤│  ├─┘───├─┘│ ││  ├┴┐├┤  │ ├┴┐├─┤└─┐├┤
┴ ┴└─┘└─┘ ┴ ┴┴ ┴└─┘┴     ┴  └─┘└─┘┴ ┴└─┘ ┴ └─┘┴ ┴└─┘└─┘${ENDCOLOR}"

echo -e "───────────────────────────────────────────────"

# Function to handle errors
handle_error() {
    echo -e "${RED}Error: $1${ENDCOLOR}"
    exit 1
}

# Remove QuickInstall App
sudo rm -rf /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase || handle_error "Failed to remove QuickInstall App"
echo -e "${START} Removed QuickInstall App ✅"

# Remove Templates
sudo rm /usr/local/hestia/data/templates/web/nginx/Pocketbase.tpl || handle_error "Failed to remove template Pocketbase.tpl"
sudo rm /usr/local/hestia/data/templates/web/nginx/Pocketbase.stpl || handle_error "Failed to remove template Pocketbase.stpl"
echo -e "${START} Removed Templates ✅"

echo -e "${GREEN}Uninstallation completed successfully!${ENDCOLOR}"

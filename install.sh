#!/bin/bash

set -e  # Exit immediately if a command exits with a non-zero status

BLUE="\e[34m"
GREEN="\e[32m"
RED="\e[31m"
YELLOW="\e[33m"
ENDCOLOR="\e[0m"
START="[${GREEN}hestiacp/${ENDCOLOR}${BLUE}pocketbase${ENDCOLOR}]"

echo -e "${GREEN}       ______                __
      /_  __/______  _______/ /_____  _____
       / / / ___/ / / / ___/ __/ __ \/ ___/
      / / / /  / /_/ (__  ) /_/ /_/ (__  )
     /_/ /_/   \__,_/____/\__/\____/____/${ENDCOLOR}"

echo -e "${BLUE}┬ ┬┌─┐┌─┐┌┬┐┬┌─┐┌─┐┌─┐   ┌─┐┌─┐┌─┐┬┌─┌─┐┌┬┐┌┐ ┌─┐┌─┐┌─┐
├─┤├┤ └─┐ │ │├─┤│  ├─┘───├─┘│ ││  ├┴┐├┤  │ ├┴┐├─┤└─┐├┤
┴ ┴└─┘└─┘ ┴ ┴┴ ┴└─┘┴     ┴  └─┘└─┘┴ ┴└─┘ ┴ └─┘┴ ┴└─┘└─┘${ENDCOLOR}"

echo -e "───────────────────────────────────────────────"

# Function to handle errors
handle_error() {
    echo -e "${RED}Error: $1${ENDCOLOR}"
    exit 1
}

# Copy QuickInstall App
sudo cp -r quickinstall-app/Pocketbase /usr/local/hestia/web/src/app/WebApp/Installers/ || handle_error "Failed to copy QuickInstall App"
echo -e "${START} Copy QuickInstall App ✅"

# Copy Templates
sudo cp templates/nginx/* /usr/local/hestia/data/templates/web/nginx || handle_error "Failed to copy nginx templates"
sudo cp templates/systemd/* /usr/local/hestia/data/templates/web/systemd || handle_error "Failed to copy systemd templates"
echo -e "${START} Copy Templates ✅"

# Change permissions
sudo chmod -R 644 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase/ || handle_error "Failed to change permissions for Pocketbase directory"
sudo chmod 755 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase || handle_error "Failed to change permissions for Pocketbase directory"
sudo chmod 755 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase/PocketbaseUtils || handle_error "Failed to change permissions for PocketbaseUtils"
sudo chmod 755 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase/templates || handle_error "Failed to change permissions for templates directory"
sudo chmod 755 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase/templates/nginx || handle_error "Failed to change permissions for nginx directory"
sudo chmod 755 /usr/local/hestia/web/src/app/WebApp/Installers/Pocketbase/templates/systemd || handle_error "Failed to change permissions for systemd directory"
echo -e "${START} Templates and QuickInstall App Permissions changed ✅"

echo -e "${GREEN}Installation completed successfully!${ENDCOLOR}"

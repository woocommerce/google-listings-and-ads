#!/bin/bash
# Copyright 2025 Google LLC
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.


# Exit if any command fails.
set -e

# Include useful functions
. "$(dirname "$0")/includes.sh"

# Stop existing containers.
status_message "Stopping old Docker containers..."
dc-wordpress down --remove-orphans --volumes

# Ensure measurement.php is up to date for testing
cd $PROJECT_DIRECTORY
composer generate

# Install library into test WordPress plugin
status_message "Initializing WordPress plugin..."
cd "${PROJECT_DIRECTORY}/examples/wordpress-plugin"
rm -rf ./vendor/
composer install

# Go back to project directory
cd $PROJECT_DIRECTORY

status_message "Update file permissions for Wordpress..."
# Wordpress expects all files to have read access and directories to have
# read/execute access.
find . -type f -name "*.php" -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Launch the containers.
status_message "Starting Docker containers..."
dc-wordpress up -d

WORDPRESS_HOST_PORT=$(dc-wordpress port $WORDPRESS_CONTAINER 80 | awk -F : '{printf $2}')
WORDPRESS_URL="http://localhost:$WORDPRESS_HOST_PORT"

# Install WordPress.
status_message "Installing WordPress..."
wp core install \
  --title="${WORDPRESS_SITE_TITLE}" \
  --admin_user="${WORDPRESS_ADMIN_USER}" \
  --admin_password="${WORDPRESS_ADMIN_PASSWORD}" \
  --admin_email="example@example.com" \
  --skip-email \
  --url="${WORDPRESS_URL}" \
  --quiet

# This will enable permalinks and activate the Google tag gateway example
# plugin. The reason we don't use the WP CLI here is that it has some
# interesting behaviors for rewrite rules. It is easier to just navigate
# the page in a headless browser and manually perform these operations. Like a
# normal user would! :)
status_message "Activating the Google tag gateway example plugin..."
npm run env:start:setup

echo -e "\n\nAccess the above install at:"
echo -e "$WORDPRESS_URL"
echo -e "Default username: $(action_format "$WORDPRESS_ADMIN_USER"), password: $(action_format "$WORDPRESS_ADMIN_PASSWORD")"

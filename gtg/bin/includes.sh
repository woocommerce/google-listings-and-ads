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


# Common variables.
PROJECT_DIRECTORY="$(cd -- "$(dirname "$0")/.."; pwd -P)"
DOCKER_COMPOSE_WORDPRESS="-f $PROJECT_DIRECTORY/docker/wordpress.yml"
# These are the containers and values for the WordPress test site.
WORDPRESS_CLI='cli'
WORDPRESS_CONTAINER='wordpress'
WORDPRESS_SITE_TITLE='Test Library'
WORDPRESS_ADMIN_USER='admin'
WORDPRESS_ADMIN_PASSWORD='password'

# TTY compatibility.
# For some environments, a TTY may not be available (e.g. GitHub Actions).
# Docker Compose allocates a TTY by default, so it's important that we disable it
# automatically when needed.
if [ -t 0 ]; then
	COMPOSE_EXEC_ARGS=""
else
	COMPOSE_EXEC_ARGS="-T" # Disable pseudo-tty allocation. By default `docker compose exec` allocates a TTY.
fi

##
# Add status message formatting to a string, and echo it.
#
# @param {string} message The string to add formatting to.
##
status_message() {
	echo -e "\033[0;36mSTATUS\033[0m: $1"
}

##
# Add formatting to an action string.
#
# @param {string} message The string to add formatting to.
##
action_format() {
	echo -en "\033[32m$1\033[0m"
}

##
# Docker Compose helper
#
# Calls `docker compose` with common options for wordpress.
##
dc-wordpress() {
	docker compose $DOCKER_COMPOSE_WORDPRESS "$@"
}

##
# WP CLI
#
# Executes a WP CLI request in the CLI container.
##
wp() {
	dc-wordpress exec $COMPOSE_EXEC_ARGS $WORDPRESS_CLI wp "$@"
}


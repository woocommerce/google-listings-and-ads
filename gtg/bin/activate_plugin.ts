/**
 * @fileoverview Run through the test WP site and activate permalinks and our
 * test plugin.
 *
 * Copyright 2025 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

import {browserHelper} from '../tests/jasmine/helpers/BrowserHelper';
import {
  WORDPRESS_ADMIN_PASSWORD,
  WORDPRESS_ADMIN_USER,
  WORDPRESS_URL,
} from '../tests/jasmine/helpers/constants';

const ADMIN_URL = WORDPRESS_URL + '/wp-admin';

async function wrapper() {

  const page = await browserHelper.setUp();
  await page.goto(ADMIN_URL);

  console.log("Navigating WP site.");
  await page.locator('#user_login').fill(WORDPRESS_ADMIN_USER);
  await page.locator('#user_pass').fill(WORDPRESS_ADMIN_PASSWORD);
  await page.locator('#wp-submit').click();
  // Wait for login to finish
  await page.waitForSelector(".welcome-panel-header-image");

  console.log("Enabling WP permalinks.");
  await page.goto(ADMIN_URL + '/options-permalink.php');
  await page.locator('#permalink-input-day-name').click();
  await page.locator('#submit').click();
  // Wait for settings to properly update
  await page.waitForSelector("#setting-error-settings_updated");

  console.log("Enabling Google Tag Gateway example plugin.");
  await page.goto(ADMIN_URL + '/plugins.php');
  await page
    .locator('#activate-example-google-tag-gateway-for-advertisers-php-library')
    .click();
  // Wait for plugin to fully enable
  await page.waitForSelector("#message");
}

async function main() {
  try {
    await wrapper();
  } catch (e) {
    console.error(e);
  } finally {
    await browserHelper.tearDown();
  }
}

main();

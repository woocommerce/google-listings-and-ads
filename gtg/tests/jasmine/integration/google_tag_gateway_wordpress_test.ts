/**
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

import 'jasmine';
import {Page} from 'puppeteer';
import {browserHelper} from '../helpers/BrowserHelper';
import {WORDPRESS_URL} from '../helpers/constants';

jasmine.DEFAULT_TIMEOUT_INTERVAL = 60000; // 1 minute test timeout

const WORDPRESS_SITE_TITLE = 'Test Library';

const GTG_ROOT_PATH =
  '/wp-content/plugins/gtg-ads-example-plugin/vendor/google' +
  '/gtg-ads/dist/measurement.php?id=G-TEST';
const GTG_DESTINATION_PATH = GTG_ROOT_PATH + '&s=';
const GTG_MEASUREMENT_PATH = '/this/is/obviously/measurement/';

describe('WordPress Plugin', () => {
  let page: Page;

  beforeEach(async () => {
    page = await browserHelper.setUp();
  });

  afterEach(async () => {
    await browserHelper.cleanPages();
  });

  afterAll(async () => {
    await browserHelper.tearDown();
  });

  it(
    'should properly forward script and collection requests through the ' +
      'WordPress server.',
    async () => {
      // Queue to wait for the script request
      const scriptUrl = WORDPRESS_URL + GTG_MEASUREMENT_PATH;
      const scriptResponse = page.waitForResponse(scriptUrl);

      // Go to the main page
      await page.goto(WORDPRESS_URL);

      // Wait for the script request
      await expectAsync(scriptResponse)
        .withContext('script request found')
        .toBeResolved();

      const script = await (await scriptResponse!).text();
      expect(script)
        .withContext('script contains php meausrement path')
        .toContain(GTG_MEASUREMENT_PATH);
    },
  );

  it(
    'should properly forward script and collection requests through the ' +
      'WordPress server with debug mode enabled.',
    async () => {
      await page.goto('https://tagassistant.google.com/');

      // Queue to wait for the WordPress page to automatically open
      const wpTarget = browserHelper
        .getBrowser()
        ?.waitForTarget((target) => target.url().startsWith(WORDPRESS_URL));

      // Start Debug Mode
      await page.locator('.wd-add-domain').click();
      await page.locator('.wd-start-debug-url-field').fill(WORDPRESS_URL);
      await page.locator('.wd-start-debugging-button').click();

      // Wait for the WP page to open
      await expectAsync(wpTarget)
        .withContext('WordPress page opened')
        .toBeResolved();

      // Bring back tag assistant to the foreground.
      await page.bringToFront();
      await page.locator('.wd-continue-debugging-button').click();

      // Wait for debug mode to recieve WP event.
      const eventRecieved = page.waitForSelector(
        `.message-list__title::-p-text("${WORDPRESS_SITE_TITLE}")`,
      );
      await expectAsync(eventRecieved)
        .withContext('debug mode recieved event from WP.')
        .toBeResolved();
    },
  );

  it('should pass a health check.', async () => {
    const url = WORDPRESS_URL + GTG_MEASUREMENT_PATH + 'healthy';
    const response = await fetch(url);
    expect(response.ok).toBeTrue();
  });

  it('should pass geo information.', async () => {
      // Queue to wait for the script request that should contain geo.
      const scriptUrl = WORDPRESS_URL + GTG_ROOT_PATH + '&geo=';
      const scriptResponse = page.waitForResponse((response) => {
        return response.url().startsWith(scriptUrl);
      });

      // Go to the main page
      await page.goto(WORDPRESS_URL + '?include_geo');

      // Wait for the script request
      await expectAsync(scriptResponse)
        .withContext('script request found')
        .toBeResolved();
  });

  it('should not pass geo information in url if present in headers.', async () => {
      // Queue to wait for the script request with no geo present.
      const scriptUrl = WORDPRESS_URL + GTG_ROOT_PATH;
      const scriptResponse = page.waitForResponse(scriptUrl);

      // Set geo headers
      await page.setExtraHTTPHeaders({
        'X-Forwarded-CountryRegion': 'US-CA',
      });

      // Go to the main page
      await page.goto(WORDPRESS_URL + '?include_geo');

      // Wait for the script request
      await expectAsync(scriptResponse)
        .withContext('script request found')
        .toBeResolved();
  });
});

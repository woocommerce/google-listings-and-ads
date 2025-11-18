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

import puppeteer, {Browser, Page} from 'puppeteer';

class BrowserHelper {
  private browser?: Browser;

  async setUp(): Promise<Page> {
    await this.launchBrowser();
    return await this.browser!.newPage();
  }

  async cleanPages(): Promise<void> {
    const pages = await this.browser?.pages();
    if (!pages) return;

    const closers = pages.map(page => page.close());
    await Promise.all(closers);
  }

  async tearDown(): Promise<void> {
    await this.closeBrowser();
  }

  getBrowser(): Browser | undefined {
    return this.browser;
  }

  private async closeBrowser(): Promise<void> {
    if (this.browser) {
      await this.browser.close();
    }
  }

  private async launchBrowser(options = {}): Promise<void> {
    if (this.browser) return;
    this.browser = await puppeteer.launch(options);
  }
}

export const browserHelper = new BrowserHelper();

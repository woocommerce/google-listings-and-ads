import { test, expect } from '@playwright/test';
import qit from '@woocommerce/qit-runtime';

/**
 * UX-002: Settings link exists on plugins page
 *
 * Severity: Low | Blocking: Yes
 * Pass criteria: a "Settings" action link is present in the plugin's row
 * on wp-admin/plugins.php after activation.
 */

test('UX-002: Settings link exists on plugins page', async ({ page }) => {
  const { username, password } = qit.env.wp;
  const slug = qit.env.sut.slug;

  // Log in to WP admin.
  await page.goto('/wp-login.php');
  await page.fill('#user_login', username);
  await page.fill('#user_pass', password);
  await page.click('#wp-submit');
  await page.waitForURL('**/wp-admin/**');

  // Navigate to the plugins list.
  await page.goto('/wp-admin/plugins.php');

  // Locate the row for this plugin and assert a Settings link is present.
  const pluginRow = page.locator(`tr[data-slug="${slug}"]`);
  await expect(pluginRow, `Plugin row for "${slug}" not found on plugins page`).toBeVisible();

  const settingsLink = pluginRow.locator('.row-actions a', { hasText: /^Settings$/i });
  await expect(
    settingsLink,
    `No "Settings" link found in the plugin row for "${slug}"`
  ).toBeVisible();
});

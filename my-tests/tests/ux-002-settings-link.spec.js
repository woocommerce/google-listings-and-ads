import { test, expect } from '@playwright/test';
import qit from '@woocommerce/qit-runtime';

/**
 * UX-002: Plugin row and primary action on plugins page
 *
 * Severity: Low | Blocking: Yes
 * Pass criteria: the SUT row exists on wp-admin/plugins.php and the row
 * contains a link (row-actions / plugin meta) labeled Get Started, Settings,
 * or Configure.
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
  await page.goto('/wp-admin/plugins.php', { waitUntil: 'load' });
  await expect(page.getByRole('heading', { name: /^Plugins$/ })).toBeVisible();

  // Scope to the installed-plugins tbody; `data-plugin` is stable for this extension.
  const pluginFile = `${slug}/${slug}.php`;
  const pluginRow = page.locator(`#the-list tr[data-plugin="${pluginFile}"]`);
  await expect(
    pluginRow,
    `Plugin row for "${slug}" not found on plugins page`
  ).toBeVisible();

  const primaryAction = pluginRow.getByRole('link', {
    name: /^(Get Started|Settings|Configure)$/i,
  });
  await expect(
    primaryAction,
    `No "Get Started", "Settings", or "Configure" link in the plugin row for "${slug}"`
  ).toBeVisible();
});

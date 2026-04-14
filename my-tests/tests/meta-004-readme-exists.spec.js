import { test, expect } from '@playwright/test';

/**
 * META-004: readme.txt exists at plugin root
 *
 * Severity: Low | Blocking: Yes
 * Required for WooCommerce Marketplace listing.
 *
 * Strategy: request the file directly via HTTP — readme.txt is publicly
 * accessible under /wp-content/plugins/<slug>/ in a standard WP install.
 */

const PLUGIN_SLUG = 'google-listings-and-ads';

test('META-004: readme.txt exists at plugin root', async ({ request }) => {
  const response = await request.get(
    `/wp-content/plugins/${PLUGIN_SLUG}/readme.txt`
  );

  expect(
    response.status(),
    `readme.txt must be present at /wp-content/plugins/${PLUGIN_SLUG}/readme.txt (required for marketplace listing)`
  ).toBe(200);
});

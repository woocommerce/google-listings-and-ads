import { test, expect } from '@playwright/test';
import qit from '@woocommerce/qit-runtime';

test('site is reachable', async ({ page }) => {
  const response = await page.goto('/');
  expect(response?.status()).toBe(200);

  await expect(page.locator('body')).toBeVisible();
});

// Example: use qit.actions() to iterate over capabilities from other packages
// for (const makePurchase of qit.actions('makePurchase')) {
//   test(`checkout via ${makePurchase.provider}`, async ({ page }) => {
//     await makePurchase(page, { amount: 29.99 });
//   });
// }

// Example: use qit.package() to access another package's exports
// const woo = qit.package('woocommerce/core-utils');
// await woo.loginAs(page, 'customer');
/**
 * External dependencies
 */
import { expect } from '@playwright/test';

export default async function optimizeCampaign(
	page,
	setupAdsAccounts,
	optimizeCampaignPage
) {
	await setupAdsAccounts.clickContinue();

	await expect(
		page.getByRole( 'heading', { name: 'Optimize your campaign' } )
	).toBeVisible();

	// For a new (not yet created) campaign, the asset group header
	// automatically loads homepage assets as soon as this step mounts, so
	// there's no Final URL picker to interact with manually - just wait for
	// that auto-load to settle.
	await expect(
		optimizeCampaignPage.getSelectDifferentFinalUrlButton()
	).toBeVisible();

	const createCampaignButton = page.locator(
		'[data-action="submit-campaign-and-assets"]'
	);

	await expect( createCampaignButton ).toBeEnabled();
}

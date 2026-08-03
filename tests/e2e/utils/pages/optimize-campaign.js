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

	await optimizeCampaignPage.selectUrlOption();

	const createCampaignButton = page.locator(
		'[data-action="submit-campaign-and-assets"]'
	);

	await expect( createCampaignButton ).toBeEnabled();
}

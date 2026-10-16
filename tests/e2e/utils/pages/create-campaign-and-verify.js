/**
 * External dependencies
 */
import { expect } from '@playwright/test';

export default async function createCampaignAndVerify(
	page,
	expectedCampaigns
) {
	const createCampaignButton = page.locator(
		'[data-action="submit-campaign-and-assets"]'
	);

	await createCampaignButton.click();

	await page.waitForURL( /path=%2Fgoogle%2Fdashboard/ );

	for ( const campaign of expectedCampaigns ) {
		await expect( page.getByText( campaign ) ).toBeVisible();
	}
}

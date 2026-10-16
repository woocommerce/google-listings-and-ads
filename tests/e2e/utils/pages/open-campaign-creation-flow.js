/**
 * External dependencies
 */
import { expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';

export default async function openCampaignCreationFlow( page, dashboardPage ) {
	await dashboardPage.addPaidCampaignButton.click();

	await page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );

	await expect(
		page.getByRole( 'heading', { name: 'Create your campaign' } )
	).toBeVisible();
}

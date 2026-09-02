/**
 * External dependencies
 */
import { expect, test } from '@playwright/test';

/**
 * Internal dependencies
 */
import {
	clearGCRNotificationsDismissed,
	clearOnboardedMerchant,
	setOnboardedMerchant,
} from '../../utils/api';
import SettingsPage from '../../utils/pages/settings';

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/settings.js').default} settingsPage
 */
let settingsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Google Customer Reviews Setting', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		settingsPage = new SettingsPage( page );

		await setOnboardedMerchant();
		await settingsPage.mockRequests();
		await settingsPage.goto();
	} );

	test.afterAll( async () => {
		await clearOnboardedMerchant();
		await clearGCRNotificationsDismissed();
		await page.close();
	} );

	test( 'should show the "Google Customer Reviews" setting card', async () => {
		await expect(
			page.getByRole( 'heading', { name: 'Settings' } )
		).toBeVisible();
		await expect(
			page.getByRole( 'heading', { name: 'Google Customer Reviews' } )
		).toBeVisible();
	} );

	test( '"Collect reviews after purchase" checkbox should be unchecked by default', async () => {
		await expect(
			settingsPage.getCollectReviewsCheckbox()
		).not.toBeChecked();
	} );

	test( '"Google store widget" checkbox should be unchecked by default, and its position control hidden', async () => {
		await expect( settingsPage.getBadgeWidgetCheckbox() ).not.toBeChecked();
		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Right bottom' )
		).not.toBeVisible();
	} );

	test( 'should persist "Collect reviews after purchase" across a reload once enabled', async () => {
		const checkbox = settingsPage.getCollectReviewsCheckbox();
		const requestPromise = settingsPage.registerGCRSettingsSaveRequest();

		await checkbox.click();
		await requestPromise;

		await page.reload();
		await expect( settingsPage.getCollectReviewsCheckbox() ).toBeChecked();
	} );

	test( 'should reveal the widget position control and persist "Google store widget" across a reload once enabled', async () => {
		const checkbox = settingsPage.getBadgeWidgetCheckbox();
		const requestPromise = settingsPage.registerGCRSettingsSaveRequest();

		await checkbox.click();
		await requestPromise;

		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Right bottom' )
		).toBeChecked();

		await page.reload();

		await expect( settingsPage.getBadgeWidgetCheckbox() ).toBeChecked();
		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Right bottom' )
		).toBeChecked();
	} );

	test( 'should persist a change to the widget position across a reload', async () => {
		const requestPromise = settingsPage.registerGCRSettingsSaveRequest();

		// `.click()`, not `.check()` — the component sets a synchronous
		// `isSaving` state before awaiting the save, which momentarily
		// re-renders the radio with its old `selected` value and trips
		// `.check()`'s own pre/post state assertion. The `toBeChecked()`
		// assertion below already retries until the real save settles.
		await settingsPage.getBadgeWidgetPositionRadio( 'Left bottom' ).click();
		await requestPromise;

		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Left bottom' )
		).toBeChecked();

		await page.reload();

		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Left bottom' )
		).toBeChecked();
	} );

	test( 'should persist "Collect reviews after purchase" as unchecked across a reload once disabled again', async () => {
		const checkbox = settingsPage.getCollectReviewsCheckbox();
		const requestPromise = settingsPage.registerGCRSettingsSaveRequest();

		await checkbox.click();
		await requestPromise;

		await expect( checkbox ).not.toBeChecked();

		await page.reload();
		await expect(
			settingsPage.getCollectReviewsCheckbox()
		).not.toBeChecked();
	} );

	test( 'should persist "Google store widget" as unchecked across a reload once disabled again, and hide the position control', async () => {
		const checkbox = settingsPage.getBadgeWidgetCheckbox();
		const requestPromise = settingsPage.registerGCRSettingsSaveRequest();

		await checkbox.click();
		await requestPromise;

		await expect( checkbox ).not.toBeChecked();
		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Left bottom' )
		).not.toBeVisible();

		await page.reload();

		await expect( settingsPage.getBadgeWidgetCheckbox() ).not.toBeChecked();
		await expect(
			settingsPage.getBadgeWidgetPositionRadio( 'Left bottom' )
		).not.toBeVisible();
	} );
} );

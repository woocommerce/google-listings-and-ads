/**
 * Internal dependencies
 */
import SetUpAccountsPage from '../../utils/pages/setup-mc/step-1-set-up-accounts';

/**
 * External dependencies
 */
const { test, expect } = require( '@playwright/test' );

test.use( { storageState: process.env.ADMINSTATE } );

test.describe.configure( { mode: 'serial' } );

/**
 * @type {import('../../utils/pages/setup-mc/step-1-set-up-accounts.js').default} setUpAccountsPage
 */
let setUpAccountsPage = null;

/**
 * @type {import('@playwright/test').Page} page
 */
let page = null;

test.describe( 'Hide Store Requirements Step', () => {
	test.beforeAll( async ( { browser } ) => {
		page = await browser.newPage();
		setUpAccountsPage = new SetUpAccountsPage( page );
		await Promise.all( [
			// Mock google as connected.
			setUpAccountsPage.mockGoogleNotConnected(),

			// Mock MC contact information
			setUpAccountsPage.mockContactInformation(),
		] );
	} );

	test.afterAll( async () => {
		await setUpAccountsPage.closePage();
	} );

	test( 'should have store requirements step if incomplete', async () => {
		await setUpAccountsPage.goto();

		// Mock MC step at step 1:
		setUpAccountsPage.mockMCSetup( 'incomplete', 'accounts' );

		// 1. Assert there are 3 steps
		const steps = await page.locator( '.woocommerce-stepper__step' );
		await expect( steps ).toHaveCount( 4 );

		// 2. Assert the label of the 3rd step is 'Confirm store requirements'
		const thirdStepLabel = await steps
			.nth( 2 )
			.locator( '.woocommerce-stepper__step-label' );
		await expect( thirdStepLabel ).toHaveText(
			'Confirm store requirements'
		);
	} );

	test( 'should not have store requirements step if complete', async () => {
		await setUpAccountsPage.goto();

		// TODO: Mock email is verified & address is filled

		// 1. Assert there are 3 steps
		const steps = await page.locator( '.woocommerce-stepper__step' );
		await expect( steps ).toHaveCount( 3 );

		// 2. Assert the label of the 3rd step is not 'Confirm store requirements'
		const thirdStepLabel = await steps
			.nth( 2 )
			.locator( '.woocommerce-stepper__step-label' );
		await expect( thirdStepLabel ).not.toHaveText(
			'Confirm store requirements'
		);

		// 3. Assert the label of the 3rd step equals 'Create a campaign'
		await expect( thirdStepLabel ).toHaveText( 'Create a campaign' );
	} );
} );

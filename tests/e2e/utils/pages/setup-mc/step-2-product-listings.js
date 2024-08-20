/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../../constants';
import MockRequests from '../../mock-requests';

/**
 * Configure product listings page object class.
 */
export default class ProductListingsPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
	}

	/**
	 * Close the current page.
	 *
	 * @return {Promise<void>}
	 */
	async closePage() {
		await this.page.close();
	}

	/**
	 * Go to the set up mc page.
	 *
	 * @return {Promise<void>}
	 */
	async goto() {
		await this.page.goto(
			'/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Fsetup-mc&google-mc=connected',
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get language radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get language radio row.
	 */
	getLanguageRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'English',
			exact: true,
		} );
	}

	/**
	 * Get selected countries only radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get selected countries only radio row.
	 */
	getSelectedCountriesOnlyRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'Selected countries only',
			exact: true,
		} );
	}

	/**
	 * Get all countries radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get all countries radio row.
	 */
	getAllCountriesRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'All countries',
			exact: true,
		} );
	}

	/**
	 * Get recommended shipping rate radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get recommended shipping rate radio row.
	 */
	getRecommendedShippingRateRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'Recommended: Automatically sync my store’s shipping settings to Google.',
			exact: true,
		} );
	}

	/**
	 * Get simple shipping rate radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get simple shipping rate radio row.
	 */
	getSimpleShippingRateRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'My shipping settings are simple. I can manually estimate flat shipping rates.',
			exact: true,
		} );
	}

	/**
	 * Get complex shipping rate radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get complex shipping rate radio row.
	 */
	getComplexShippingRateRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'My shipping settings are complex. I will enter my shipping rates and times manually in Google Merchant Center.',
			exact: true,
		} );
	}

	/**
	 * Get offer free shipping for orders button.
	 *
	 * @param {string} name
	 *
	 * @return {import('@playwright/test').Locator} Get offer free shipping for orders button.
	 */
	getOfferFreeShippingForOrdersRadioRow( name = 'Yes' ) {
		return this.page.getByRole( 'radio', {
			name,
			exact: true,
		} );
	}

	/**
	 * Get destination-based tax rate radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get destination-based tax rate radio row.
	 */
	getDestinationBasedTaxRateRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'My store uses destination-based tax rates.',
			exact: true,
		} );
	}

	/**
	 * Get non-destination-based tax rate radio row.
	 *
	 * @return {import('@playwright/test').Locator} Get non-destination-based tax rate radio row.
	 */
	getNonDestinationBasedTaxRateRadioRow() {
		return this.page.getByRole( 'radio', {
			name: 'My store does not use destination-based tax rates.',
			exact: true,
		} );
	}

	/**
	 * Get shipping rates section.
	 *
	 * @return {import('@playwright/test').Locator} Get shipping rates section.
	 */
	getShippingRatesSection() {
		return this.page
			.locator( 'section' )
			.filter( { hasText: 'Shipping rates' } );
	}

	/**
	 * Get shipping times section.
	 *
	 * @return {import('@playwright/test').Locator} Get shipping times section.
	 */
	getShippingTimesSection() {
		return this.page
			.locator( 'section' )
			.filter( { hasText: 'Shipping times' } );
	}

	/**
	 * Get tax rate section.
	 *
	 * @return {import('@playwright/test').Locator} Get tax rate section.
	 */
	getTaxRateSection() {
		return this.page
			.locator( 'section' )
			.filter( { hasText: 'Tax rate (required for U.S. only)' } );
	}

	/**
	 * Get audience card.
	 *
	 * @return {import('@playwright/test').Locator} Get audience card.
	 */
	getAudienceCard() {
		return this.page
			.locator( '.components-card' )
			.filter( { hasText: 'Selected countries only' } );
	}

	/**
	 * Get estimated shipping rates card.
	 *
	 * @return {import('@playwright/test').Locator} Get estimated shipping rates card.
	 */
	getEstimatedShippingRatesCard() {
		return this.page
			.locator( '.components-card' )
			.filter( { hasText: 'Estimated shipping rates' } );
	}

	/**
	 * Get estimated shipping times card.
	 *
	 * @return {import('@playwright/test').Locator} Get estimated shipping times card.
	 */
	getEstimatedShippingTimesCard() {
		return this.page
			.locator( '.components-card' )
			.filter( { hasText: 'Estimated shipping times' } );
	}

	/**
	 * Get estimated shipping rates input box.
	 *
	 * @return {import('@playwright/test').Locator} Get estimated shipping rates input box.
	 */
	getEstimatedShippingRatesInputBox() {
		return this.getEstimatedShippingRatesCard().locator(
			'input[id*="inspector-input-control"]'
		);
	}

	/**
	 * Get estimated shipping times input box.
	 *
	 * @return {import('@playwright/test').Locator} Get estimated shipping times input box.
	 */
	getEstimatedShippingTimesInputBox() {
		return this.getEstimatedShippingTimesCard().locator(
			'input[id*="inspector-input-control"]'
		);
	}

	/**
	 * Get estimated shipping times error.
	 *
	 * @return {import('@playwright/test').Locator} Get estimated shipping times error.
	 */
	getEstimatedShippingTimesError() {
		return this.getEstimatedShippingTimesCard().getByText(
			'Please specify estimated shipping times for all the countries, and the time cannot be less than 0'
		);
	}

	/**
	 * Get tax rate error.
	 *
	 * @return {import('@playwright/test').Locator} Get tax rate error.
	 */
	getTaxRateError() {
		return this.getTaxRateSection().getByText(
			'Please specify tax rate option.'
		);
	}

	/**
	 * Get "Free shipping for all orders" tag.
	 *
	 * @return {import('@playwright/test').Locator} Get "Free shipping for all orders" tag.
	 */
	getFreeShippingForAllOrdersTag() {
		return this.getEstimatedShippingRatesCard().getByText(
			'Free shipping for all orders'
		);
	}

	/**
	 * Get "I offer free shipping for orders over a certain price" text.
	 *
	 * @return {import('@playwright/test').Locator} Get "I offer free shipping for orders over a certain price" text.
	 */
	getOfferFreeShippingForOrdersText() {
		return this.page.getByText(
			'I offer free shipping for orders over a certain price'
		);
	}

	/**
	 * Get "Minimum order to qualify for free shipping" text.
	 *
	 * @return {import('@playwright/test').Locator} Get "Minimum order to qualify for free shipping" text.
	 */
	getMinimumOrderForFreeShippingText() {
		return this.page.getByText(
			'Minimum order to qualify for free shipping'
		);
	}

	/**
	 * Get "Continue" button.
	 *
	 * @return {import('@playwright/test').Locator} Get "Continue" button.
	 */
	getContinueButton() {
		return this.page.getByRole( 'button', {
			name: 'Continue',
			exact: true,
		} );
	}

	/**
	 * Get "Read more" for Language link.
	 *
	 * @return {import('@playwright/test').Locator} Get "Read more" for Language link.
	 */
	getReadMoreLanguageLink() {
		return this.getAudienceCard().getByRole( 'link', {
			name: 'Read more',
			exact: true,
		} );
	}

	/**
	 * Get "Read more" for Shipping rates link.
	 *
	 * @return {import('@playwright/test').Locator} Get "Read more" for Shipping rates link.
	 */
	getReadMoreShippingRatesLink() {
		return this.getShippingRatesSection().getByRole( 'link', {
			name: 'Read more',
			exact: true,
		} );
	}

	/**
	 * Get "Read more" for Shipping times link.
	 *
	 * @return {import('@playwright/test').Locator} Get "Read more" for Shipping times link.
	 */
	getReadMoreShippingTimesLink() {
		return this.getShippingTimesSection().getByRole( 'link', {
			name: 'Read more',
			exact: true,
		} );
	}

	/**
	 * Get "Read more" for Tax rate link.
	 *
	 * @return {import('@playwright/test').Locator} Get "Read more" for Tax rate link.
	 */
	getReadMoreTaxRateLink() {
		return this.getTaxRateSection().getByRole( 'link', {
			name: 'Read more',
			exact: true,
		} );
	}

	/**
	 * Register the requests when the continue button is clicked.
	 *
	 * @return {Promise<import('@playwright/test').Request[]>} The requests.
	 */
	registerContinueRequests() {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/contact-information' ) &&
				request.method() === 'GET'
		);
	}

	/**
	 * Register settings request when the shipping rate radio button is checked.
	 *
	 * @param {string} shippingRate
	 * @return {Promise<import('@playwright/test').Request>} The requests.
	 */
	registerShippingRateRadioButtonRequests( shippingRate ) {
		return this.page.waitForRequest(
			( request ) =>
				request.url().includes( '/gla/mc/settings' ) &&
				request.method() === 'POST' &&
				request.postDataJSON().shipping_rate === shippingRate
		);
	}

	/**
	 * Click "Continue" button.
	 *
	 * @return {Promise<void>}
	 */
	async clickContinueButton() {
		const continueButton = this.getContinueButton();
		await continueButton.click();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check all countries radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkAllCountriesRadioButton() {
		const allCountriesRadioRow = this.getAllCountriesRadioRow();
		await allCountriesRadioRow.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check selected countries only radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkSelectedCountriesOnlyRadioButton() {
		const selectedCountriesOnlyRadioButton =
			this.getSelectedCountriesOnlyRadioRow();
		await selectedCountriesOnlyRadioButton.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check recommended shipping rate radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkRecommendedShippingRateRadioButton() {
		const recommendedShippingRateRadioButton =
			this.getRecommendedShippingRateRadioRow();
		await recommendedShippingRateRadioButton.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check simple shipping rate radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkSimpleShippingRateRadioButton() {
		const simpleShippingRateRadioButton =
			this.getSimpleShippingRateRadioRow();
		await simpleShippingRateRadioButton.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check simple shipping rate radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkComplexShippingRateRadioButton() {
		const complexShippingRateRadioButton =
			this.getComplexShippingRateRadioRow();
		await complexShippingRateRadioButton.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check offer free shipping for order "Yes" radio button.
	 *
	 * @param {string} name
	 *
	 * @return {Promise<void>}
	 */
	async checkOfferFreeShippingForOrdersRadioButton( name = 'Yes' ) {
		const radio = this.getOfferFreeShippingForOrdersRadioRow( name );
		await radio.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check destination-based tax rate radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkDestinationBasedTaxRateRadioButton() {
		const radio = this.getDestinationBasedTaxRateRadioRow();
		await radio.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Check non-destination-based tax rate radio button.
	 *
	 * @return {Promise<void>}
	 */
	async checkNonDestinationBasedTaxRateRadioButton() {
		const radio = this.getNonDestinationBasedTaxRateRadioRow();
		await radio.check();
		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Fill estimated shipping rates.
	 *
	 * @param {string} rate
	 *
	 * @return {Promise<void>}
	 */
	async fillEstimatedShippingRates( rate = '0' ) {
		const estimatedRatesInputBox = this.getEstimatedShippingRatesInputBox();
		await estimatedRatesInputBox.fill( rate );

		// A hack to finish typing in the input box, similar to pressing anywhere in the page.
		await estimatedRatesInputBox.press( 'Tab' );

		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}

	/**
	 * Fill estimated shipping times.
	 *
	 * @param {string} days
	 *
	 * @return {Promise<void>}
	 */
	async fillEstimatedShippingTimes( days = '0' ) {
		const estimatedTimesInputBox = this.getEstimatedShippingTimesInputBox();
		await estimatedTimesInputBox.fill( days );

		// A hack to finish typing in the input box, similar to pressing anywhere in the page.
		await estimatedTimesInputBox.press( 'Tab' );

		await this.page.waitForLoadState( LOAD_STATE.DOM_CONTENT_LOADED );
	}
}

/**
 * External dependencies
 */
import { expect } from '@playwright/test';

/**
 * Internal dependencies
 */
import * as api from './api';

async function getAvailableProductAttributesWithTestValues(
	locator,
	funcGetDateAndTime
) {
	const { dateInput: availabilityDate, timeInput: availabilityTime } =
		funcGetDateAndTime( locator );

	const mpn = locator.getByLabel( 'MPN' );
	const brand = locator.getByLabel( 'Brand', { exact: true } );
	const condition = locator.getByLabel( 'Condition', { exact: true } );
	const gender = locator.getByLabel( 'Gender', { exact: true } );
	const size = locator.getByLabel( 'Size', { exact: true } );
	const sizeSystem = locator.getByLabel( 'Size system' );
	const sizeType = locator.getByLabel( 'Size type' );
	const color = locator.getByLabel( 'Color', { exact: true } );
	const material = locator.getByLabel( 'Material', { exact: true } );
	const pattern = locator.getByLabel( 'Pattern', { exact: true } );
	const ageGroup = locator.getByLabel( 'Age Group', { exact: true } );
	const multipack = locator.getByLabel( 'Multipack', { exact: true } );
	const isBundle = locator.getByLabel( 'Is Bundle' );
	const adultContent = locator.getByLabel( 'Adult content' );

	const allPairs = [
		[ mpn, 'GO12345OOGLE' ],
		[ brand, 'e2e_test_woocommerce_brands' ],
		[ condition, 'new' ],
		[ gender, 'unisex' ],
		[ size, 'Good for everybody' ],
		[ sizeSystem, 'JP' ],
		[ sizeType, 'regular' ],
		[ color, 'Cherry blossom' ],
		[ material, 'Titanium alloy' ],
		[ pattern, 'Cyberpunk' ],
		[ ageGroup, 'kids' ],
		[ multipack, '9999' ],
		[ isBundle, 'no' ],
		[ availabilityDate, '2024-02-29' ],
		[ availabilityTime, '23:59' ],
		[ adultContent, 'no' ],
	];

	const availablePairs = [];

	for ( const pair of allPairs ) {
		if ( await pair[ 0 ].isVisible() ) {
			availablePairs.push( pair );
		}
	}

	return availablePairs;
}

async function setAttributeValue( locator, value ) {
	const tagName = await locator.evaluate( ( element ) => element.tagName );

	if ( ( await locator.isEditable() ) === false ) {
		return;
	}

	if ( tagName === 'SELECT' ) {
		await locator.selectOption( value );
	} else {
		await locator.fill( value );
	}

	await expect( locator ).toHaveValue( value );
}

/**
 * Gets E2E test utils for facilitating writing tests for the classic product editor.
 *
 * @param {import('@playwright/test').Page} page Playwright page object.
 */
export function getClassicProductEditorUtils( page ) {
	const locators = {
		getPluginTab() {
			return page.locator( '.gla_attributes_tab' );
		},

		getPluginPanel() {
			return page.locator( '#gla_attributes' );
		},

		getPluginVariationMetaBox() {
			return page.locator( '.gla-metabox:visible' ).first();
		},

		getChannelVisibilityMetaBox() {
			return page.locator( '#channel_visibility' );
		},

		getChannelVisibilityMetaBoxContent() {
			const metaBox = this.getChannelVisibilityMetaBox();
			return metaBox.locator( '.gla-channel-visibility__content' );
		},

		getChannelVisibilityHeading() {
			return this.getChannelVisibilityMetaBox().getByRole( 'heading', {
				name: 'Channel visibility',
			} );
		},

		getProductAttributesHeading() {
			return this.getPluginPanel().getByRole( 'heading', {
				name: 'Product attributes',
			} );
		},

		getChannelVisibility() {
			const metaBox = this.getChannelVisibilityMetaBox();

			return {
				selection: metaBox.getByRole( 'combobox' ),
				notice: metaBox.locator( '.components-notice' ),
				status: metaBox.locator(
					'.gla-channel-visibility__sync-status'
				),
				issues: metaBox.getByRole( 'listitem' ),
			};
		},

		getSelectWithTextInput() {
			const field = page.locator( '.select-with-text-input' ).first();

			return {
				selection: field.getByRole( 'combobox' ),
				input: field.getByRole( 'textbox' ),
			};
		},

		getDateAndTimeInputs( locator = page ) {
			const simple = locator.locator(
				'.gla_attributes_availabilityDate_field'
			);
			const variation = locator.locator(
				'.gla_variation_attributes\\[0\\]_availabilityDate_field'
			);
			const field = simple.or( variation );

			return {
				dateInput: field.locator( 'input[type=date]' ),
				timeInput: field.locator( 'input[type=time]' ),
			};
		},

		getMultipackInput() {
			return page.locator( '.gla_attributes_multipack_field input' );
		},

		getPostID() {
			const url = new URL( page.url() );
			return url.searchParams.get( 'post' );
		},

		async getAvailableProductAttributesWithTestValues( locator = page ) {
			return getAvailableProductAttributesWithTestValues(
				locator,
				this.getDateAndTimeInputs
			);
		},
	};

	const asyncActions = {
		async gotoAddProductPage() {
			await page.goto( '/wp-admin/post-new.php?post_type=product' );
			await this.waitForInteractionReady();
		},

		async gotoEditProductPage( id ) {
			await page.goto( `/wp-admin/post.php?post=${ id }&action=edit` );
			await this.waitForInteractionReady();
		},

		async gotoEditVariableProductPage() {
			const variableId = await api.createVariableWithVariationProducts();

			await page.goto(
				`/wp-admin/post.php?post=${ variableId }&action=edit`
			);
			await this.waitForInteractionReady();
		},

		async gotoEditVariation() {
			await page.locator( '.variations_tab' ).click();

			const variation = page.locator( '.woocommerce_variation' ).first();

			await variation.getByRole( 'link', { name: 'Edit' } ).click();

			return variation
				.getByRole( 'heading', { name: 'Google for WooCommerce' } )
				.click();
		},

		clickSave() {
			return page
				.getByRole( 'button', { name: /^(Save Draft|Update)$/ } )
				.click();
		},

		async save() {
			const observer = page.waitForResponse( ( response ) => {
				const url = new URL( response.url() );

				return (
					url.pathname === '/wp-admin/post.php' &&
					url.searchParams.has( 'post' ) &&
					url.searchParams.has( 'action', 'edit' ) &&
					response.ok() &&
					response.request().method() === 'GET'
				);
			} );

			await this.clickSave();
			await observer;
			await this.waitForInteractionReady();
		},

		clickPublish() {
			return page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();
		},

		async publish() {
			const observer = page.waitForResponse( ( response ) => {
				const url = new URL( response.url() );

				return (
					url.pathname === '/wp-admin/post.php' &&
					url.searchParams.has( 'post' ) &&
					url.searchParams.has( 'action', 'edit' ) &&
					response.ok() &&
					response.request().method() === 'GET'
				);
			} );

			await this.clickPublish();
			await observer;
			await this.waitForInteractionReady();
		},

		async unpublish() {
			const btn = page.getByRole( 'button', { name: 'Edit status' } );

			await btn.click();
			await page
				.getByLabel( 'Set status' )
				.selectOption( { label: 'Draft' } );
			await this.clickSave();
			await this.waitForInteractionReady();
		},

		waitForInteractionReady() {
			// Avoiding tests may start to operate the UI before jQuery interactions are initialized,
			// leading to random failures.
			return expect(
				page.locator( '.product_data_tabs li.active' )
			).toHaveCount( 1 );
		},

		async clickPluginTab() {
			return this.getPluginTab().click();
		},

		async changeProductType( type ) {
			await page.locator( '#product-type' ).selectOption( type );
		},

		changeToSimpleProduct() {
			return this.changeProductType( 'simple' );
		},

		changeToGroupedProduct() {
			return this.changeProductType( 'grouped' );
		},

		changeToExternalProduct() {
			return this.changeProductType( 'external' );
		},

		changeToVariableProduct() {
			return this.changeProductType( 'variable' );
		},

		async fillProductName( name = 'Cat Teaser' ) {
			const input = page.getByRole( 'textbox', { name: 'Product name' } );

			await input.fill( name );

			// After filling in the product name and losing focus for the first time,
			// an auto-save is triggered and a permanent link is generated. Wait here
			// for these processes to complete to avoid some random race conditions.
			await input.blur();
			await expect( page.locator( '#sample-permalink' ) ).toBeVisible();
		},

		async setChannelVisibility( label = 'Sync and show' ) {
			const channelVisibilityMetabox =
				await locators.getChannelVisibility().selection;
			await channelVisibilityMetabox.selectOption( { label } );
		},

		async setVisibility( visibility = 'Public' ) {
			const btn = page.getByRole( 'button', { name: 'Edit visibility' } );
			await btn.click();
			await page.getByLabel( visibility ).check();
		},

		evaluateValidity( input ) {
			return input.evaluate( ( element ) => element.validity.valid );
		},

		setAttributeValue,
	};

	const mocks = {
		async mockChannelVisibility( syncStatus, issues = [] ) {
			const url = new URL( page.url() );
			const productId = url.searchParams.get( 'post' );

			await api.api().put( `products/${ productId }`, {
				meta_data: [
					{ key: '_wc_gla_sync_status', value: syncStatus },
					{ key: '_wc_gla_errors', value: issues },
				],
			} );
		},
	};

	return {
		...locators,
		...asyncActions,
		...mocks,
	};
}

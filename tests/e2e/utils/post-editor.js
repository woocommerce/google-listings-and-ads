/**
 * External dependencies
 */
import { expect } from '@playwright/test';

/**
 * Internal dependencies
 */
const config = require( '../config/default.json' );

/**
 * Cookie the GLA Test Snippets plugin reads to open posts in the Classic Editor.
 */
const CLASSIC_EDITOR_COOKIE = 'gla_e2e_classic_editor';

/**
 * Gets E2E test utils for facilitating writing tests for the classic post editor.
 *
 * @param {import('@playwright/test').Page} page Playwright page object.
 */
export function getClassicPostEditorUtils( page ) {
	const locators = {
		getBusinessProfileMetaBox() {
			return page.locator( '#business_profile_share' );
		},

		getBusinessProfileShareToggle() {
			return this.getBusinessProfileMetaBox().getByRole( 'checkbox', {
				name: 'Share on my Business Profile',
			} );
		},

		getBusinessProfileSummary() {
			return this.getBusinessProfileMetaBox().getByRole( 'textbox', {
				name: 'Summary',
			} );
		},

		getBusinessProfileSummaryCount() {
			return this.getBusinessProfileMetaBox().getByText(
				/\d+\/\d+ characters/
			);
		},

		getTitleInput() {
			return page.getByRole( 'textbox', { name: 'Add title' } );
		},

		getPostID() {
			const url = new URL( page.url() );
			return url.searchParams.get( 'post' );
		},
	};

	const asyncActions = {
		/**
		 * Makes the editor open posts in the Classic Editor for this browser context.
		 */
		async useClassicEditor() {
			await page.context().addCookies( [
				{
					name: CLASSIC_EDITOR_COOKIE,
					value: '1',
					url: config.url,
				},
			] );
		},

		async gotoAddPostPage() {
			await page.goto( '/wp-admin/post-new.php' );
			await this.waitForInteractionReady();
		},

		async fillTitle( title = 'Fresh from the oven' ) {
			await this.getTitleInput().fill( title );
		},

		/**
		 * Clicks Publish and resolves with the form submission the click sends.
		 *
		 * @return {Promise<import('@playwright/test').Request>} The request submitting the post's form.
		 */
		async publish() {
			const requestPromise = page.waitForRequest( ( request ) => {
				const url = new URL( request.url() );

				return (
					url.pathname === '/wp-admin/post.php' &&
					request.method() === 'POST'
				);
			} );

			await page
				.getByRole( 'button', { name: 'Publish', exact: true } )
				.click();

			return requestPromise;
		},

		waitForInteractionReady() {
			return expect( page.locator( '#publish' ) ).toBeVisible();
		},
	};

	return {
		...locators,
		...asyncActions,
	};
}

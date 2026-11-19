/**
 * Internal dependencies
 */
import { LOAD_STATE } from '../constants';
import MockRequests from '../mock-requests';

/**
 * Reports page object class.
 */
export default class ReportsPage extends MockRequests {
	/**
	 * @param {import('@playwright/test').Page} page
	 */
	constructor( page ) {
		super( page );
		this.page = page;
		this.subNav = this.page.locator( '.gla-sub-nav' );
		this.programsTable = this.page.locator( '.woocommerce-table' ).filter( {
			has: this.page.getByRole( 'heading', {
				name: 'Programs',
				exact: true,
			} ),
		} );
	}

	/**
	 * Go to the Programs report page.
	 *
	 * @param {string} [reportKey='programs'] The report to open, 'programs' or 'products'.
	 * @return {Promise<void>}
	 */
	async goto( reportKey = 'programs' ) {
		await this.page.goto(
			`/wp-admin/admin.php?page=wc-admin&path=%2Fgoogle%2Freports&reportKey=${ reportKey }`,
			{ waitUntil: LOAD_STATE.DOM_CONTENT_LOADED }
		);
	}

	/**
	 * Get the sub-navigation link titles.
	 *
	 * @return {Promise<string[]>} An array of sub-navigation titles in display order.
	 */
	async getSubNavTitles() {
		const links = this.subNav.locator( 'a' );
		await links.first().waitFor( { state: 'visible' } );
		return ( await links.allTextContents() ).map( ( t ) => t.trim() );
	}
}

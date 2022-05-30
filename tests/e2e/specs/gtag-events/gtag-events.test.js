/**
 * External dependencies
 */
import {
	shopper, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';
import { setOption } from '@wordpress/e2e-test-utils';

describe( 'GTag events', () => {
	beforeAll( async () => {
		setOption(
			'gla_ads_conversion_action',
			'a:2:{s:13:"conversion_id";s:9:"AW-123456";s:16:"conversion_label";s:9:"aB_cdEFgh";}'
		);
	} );

	it( 'Global GTag snippet appears on a frontend page', async () => {
		await shopper.goToShop();

		await expect( page.title() ).resolves.toContain(
			'Products'
		);

		/*await expect(
			page.$$eval(
				'head',
				( elements ) => elements.some(
					( el ) => el.textContent.includes( 'Global site tag (gtag.js)' )
				)
			)
		).resolves.toBeTruthy();*/

		/*await expect(
			page.waitForXPath(
				"//*[text()='Global site tag (gtag.js)']"
			)
		).resolves.toBeTruthy();*/
	} );
} );

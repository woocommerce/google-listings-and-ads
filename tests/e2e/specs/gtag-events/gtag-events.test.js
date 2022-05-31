/**
 * External dependencies
 */
import {
	shopper, // eslint-disable-line import/named
} from '@woocommerce/e2e-utils';

/**
 * Internal dependencies
 */
import {
	clearConversionID,
	saveConversionID,
} from '../../utils/connection-test-page';

describe( 'GTag events', () => {
	beforeAll( async () => {
		await saveConversionID();
	} );

	afterAll( async () => {
		await clearConversionID();
	} );

	it( 'Global GTag snippet appears on a frontend page', async () => {
		await shopper.goToShop();

		await expect(
			page.$$eval( 'head', ( elements ) =>
				elements.some( ( el ) =>
					el.innerHTML.includes( 'Global site tag (gtag.js)' )
				)
			)
		).resolves.toBeTruthy();
	} );
} );

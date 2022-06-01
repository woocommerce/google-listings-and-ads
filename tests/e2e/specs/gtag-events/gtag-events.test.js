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
import { trackGtagEvent } from '../../utils/track-event';

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

	it( 'Page view event is sent on a frontend page', async () => {
		const event = trackGtagEvent( 'page_view' );

		await shopper.goToShop();
		await expect( event ).resolves.toBeTruthy();
	} );
} );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useSettings from '~/hooks/useSettings';
import MarketNotice from '.';

jest.mock( '~/hooks/useSettings' );

describe( 'MarketNotice', () => {
	beforeEach( () => {
		global.glaData.isMultiLingualStore = false;
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
	} );

	test( 'renders when shipping_rate is manual and single lingual store', () => {
		render( <MarketNotice /> );

		const notice = document.querySelector( '.gla-market-notice' );
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveTextContent(
			'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your Merchant Center account.'
		);
	} );

	test( 'renders null when isMultiLingualStore is true', () => {
		global.glaData.isMultiLingualStore = true;

		render( <MarketNotice /> );

		expect(
			document.querySelector( '.gla-market-notice' )
		).not.toBeInTheDocument();
	} );

	test( 'renders null when shipping_rate is not manual', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat' },
		} );

		render( <MarketNotice /> );

		expect(
			document.querySelector( '.gla-market-notice' )
		).not.toBeInTheDocument();
	} );
} );

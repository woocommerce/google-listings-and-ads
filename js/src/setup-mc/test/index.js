/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import CurrencyFactory from '@woocommerce/currency';
import { SETTINGS_STORE_NAME } from '@woocommerce/data';
import { getCurrentDates } from '@woocommerce/date';
import { getQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import SetupMC from '../';

describe( 'SetupMC', () => {
	test( 'should be able to run unit-test with @woocommerce/components', () => {
		const { getByText } = render( <SetupMC /> );

		expect(
			getByText( 'Get started with Google Listings & Ads' )
		).toBeTruthy();
	} );

	test( 'should be able to run unit-test with @woocommerce/currency', () => {
		expect( CurrencyFactory ).toBeInstanceOf( Function );
	} );

	test( 'should be able to run unit-test with @woocommerce/data', () => {
		expect( SETTINGS_STORE_NAME ).toBe( 'wc/admin/settings' );
	} );

	test( 'should be able to run unit-test with @woocommerce/date', () => {
		expect( getCurrentDates ).toBeInstanceOf( Function );
	} );

	test( 'should be able to run unit-test with @woocommerce/navigation', () => {
		expect( getQuery ).toBeInstanceOf( Function );
	} );

	test( 'should be able to run unit-test with @woocommerce/number', () => {
		expect( numberFormat ).toBeInstanceOf( Function );
	} );

	test( 'should be able to run unit-test with @woocommerce/tracks', () => {
		expect( recordEvent ).toBeInstanceOf( Function );
	} );
} );

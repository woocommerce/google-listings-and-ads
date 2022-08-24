/**
 * External dependencies
 */
import { Link } from '@woocommerce/components';
import CurrencyFactory from '@woocommerce/currency';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { getCurrentDates } from '@woocommerce/date';
import { getQuery } from '@woocommerce/navigation';
import { numberFormat } from '@woocommerce/number';
import { recordEvent } from '@woocommerce/tracks';

// Test `@woocommerce/*` packages are not breaking our tests
// https://github.com/woocommerce/woocommerce-admin/issues/6483
// https://github.com/woocommerce/google-listings-and-ads/pull/649
describe( 'plugin config should be able to run unit-test with dependency on', () => {
	test( '@woocommerce/components', () => {
		expect( Link ).toBeDefined();
	} );

	test( '@woocommerce/currency', () => {
		expect( CurrencyFactory ).toBeInstanceOf( Function );
	} );

	test( '@woocommerce/data', () => {
		expect( OPTIONS_STORE_NAME ).toBeDefined();
	} );

	test( '@woocommerce/date', () => {
		expect( getCurrentDates ).toBeInstanceOf( Function );
	} );

	test( '@woocommerce/navigation', () => {
		expect( getQuery ).toBeInstanceOf( Function );
	} );

	test( '@woocommerce/number', () => {
		expect( numberFormat ).toBeInstanceOf( Function );
	} );

	test( '@woocommerce/tracks', () => {
		expect( recordEvent ).toBeInstanceOf( Function );
	} );
} );

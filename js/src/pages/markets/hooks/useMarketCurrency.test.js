/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMarketCurrency from './useMarketCurrency';
import useStoreCurrency from '~/hooks/useStoreCurrency';

const storeCurrencyConfig = {
	code: 'USD',
	precision: 2,
	symbol: '$',
	symbolPosition: 'left',
	decimalSeparator: '.',
	thousandSeparator: ',',
	priceFormat: '%1$s%2$s',
};

jest.mock( '~/hooks/useStoreCurrency', () =>
	jest
		.fn()
		.mockName( 'useStoreCurrency' )
		.mockReturnValue( storeCurrencyConfig )
);

describe( 'useMarketCurrency', () => {
	test( 'returns an object with a formatAmount function', () => {
		const { result } = renderHook( () => useMarketCurrency() );

		expect( result.current ).toMatchObject( {
			formatAmount: expect.any( Function ),
		} );
	} );

	describe( 'formatAmount', () => {
		test( 'includes the given currency code in the output', () => {
			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			expect( formatAmount( 50, 'EUR' ) ).toContain( 'EUR' );
			expect( formatAmount( 50, 'JPY' ) ).toContain( 'JPY' );
		} );

		test( 'includes the numeric amount in the output', () => {
			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			expect( formatAmount( 50, 'EUR' ) ).toContain( '50' );
			expect( formatAmount( 1234.5, 'USD' ) ).toContain( '1,234.50' );
		} );

		test( 'applies store decimal and thousands separators', () => {
			// storeCurrencyConfig uses '.' as decimal and ',' as thousands
			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			// 1234.567 rounded to precision 2 → 1,234.57
			expect( formatAmount( 1234.567, 'EUR' ) ).toContain( '1,234.57' );
		} );

		test( 'applies store precision', () => {
			// storeCurrencyConfig has precision: 2
			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			expect( formatAmount( 10, 'EUR' ) ).toContain( '10.00' );
		} );

		test( 'uses store precision when a different-precision currency is given', () => {
			useStoreCurrency.mockReturnValueOnce( {
				...storeCurrencyConfig,
				precision: 0,
				priceFormat: '%1$s%2$s',
			} );

			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			// precision 0 → no decimal places
			expect( formatAmount( 50.99, 'JPY' ) ).not.toContain( '.' );
			expect( formatAmount( 50.99, 'JPY' ) ).toContain( '51' );
		} );

		test( 'falls back to store currency when no code is provided', () => {
			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			// No currencyCode → uses storeCurrencySetting defaults.
			// CurrencyFactory uses the symbol ('$') in its output by default,
			// not the ISO code, so we assert on the symbol.
			const formatted = formatAmount( 50 );
			expect( formatted ).toContain( '50' );
			expect( formatted ).toContain( '$' );
		} );

		test( 'respects a right-positioned price format from the store', () => {
			useStoreCurrency.mockReturnValueOnce( {
				...storeCurrencyConfig,
				priceFormat: '%2$s %1$s',
			} );

			const { result } = renderHook( () => useMarketCurrency() );
			const { formatAmount } = result.current;

			// priceFormat '%2$s %1$s' → amount first, then code (e.g. '50.00 EUR')
			const formatted = formatAmount( 50, 'EUR' );
			expect( formatted ).toMatch( /50\.00\sEUR/ );
		} );
	} );
} );

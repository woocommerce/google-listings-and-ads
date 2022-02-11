/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useMetricsWithFormatter from './useMetricsWithFormatter';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest
		.fn()
		.mockName( "getSetting( 'currency' )" )
		.mockReturnValue( {
			code: 'EUR',
			symbol: '€',
			precision: 2,
			decimalSeparator: '.',
			thousandSeparator: ',',
			priceFormat: '%1$s %2$s',
		} ),
} ) );

jest.mock( '.~/hooks/useGoogleAdsAccount', () =>
	jest
		.fn()
		.mockName( 'useGoogleAdsAccount' )
		.mockReturnValue( {
			googleAdsAccount: {
				currency: 'JPY',
				symbol: '¥',
			},
		} )
);

describe( 'useMetricsWithFormatter', () => {
	let metrics;
	beforeEach( () => {
		metrics = [
			{ key: 'sales', label: 'Sales', isCurrency: true },
			{ key: 'clicks', label: 'Clicks', isCurrency: false },
		];
	} );

	it( 'should return an array with shallow copied metrics', () => {
		const { result } = renderHook( () =>
			useMetricsWithFormatter( metrics )
		);

		result.current.forEach( ( metric, idx ) => {
			expect( metric ).not.toBe( metrics[ idx ] );
			expect( metric ).toMatchObject( metrics[ idx ] );
		} );
	} );

	describe( 'should have a `formatFn` function in each metrics', () => {
		it( "when calling with `undefined`, `formatFn` should return 'Unavailable'", () => {
			const { result } = renderHook( () =>
				useMetricsWithFormatter( metrics )
			);

			result.current.forEach( ( metric ) => {
				expect( metric.formatFn( undefined ) ).toBe( 'Unavailable' );
			} );
		} );

		it( '`formatFn` should return formatted string according to `metric.isCurrency`', () => {
			const { result } = renderHook( () =>
				useMetricsWithFormatter( metrics )
			);

			const [ sales, clicks ] = result.current;

			expect( sales.formatFn( 1234.555 ) ).toBe( 'JPY\xA01,234.56' );
			expect( clicks.formatFn( 1234 ) ).toBe( '1,234' );
		} );
	} );
} );

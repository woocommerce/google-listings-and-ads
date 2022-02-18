/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { apiFetch } from '@wordpress/data-controls';
import { dispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { STORE_KEY } from '.~/data';
import useAdsCurrency from './useAdsCurrency';
import useStoreCurrency from './useStoreCurrency';

// Force real timers, make sure all timers are actually ticking.
jest.useRealTimers();

jest.mock( '@wordpress/data-controls', () => ( {
	apiFetch: jest.fn().mockName( 'apiFetch mock' ),
} ) );
jest.mock( './useStoreCurrency', () => ( {
	__esModule: true,
	default: jest.fn().mockName( 'useStoreCurrency' ).mockReturnValue( {
		code: 'EUR',
		precision: 3,
		symbol: '€',
		symbolPosition: 'right_space',
		decimalSeparator: ';',
		thousandSeparator: '|',
		priceFormat: '%2$s %1$s',
	} ),
} ) );

describe( 'useAdsCurrency', () => {
	test( 'initially should return `{ adsCurrencyConfig: Object, hasFinishedResolution: undefined, formatAmount: Function }`', () => {
		const { result } = renderHook( () => useAdsCurrency() );

		// assert initial state
		expect( result.current ).toHaveProperty(
			'hasFinishedResolution',
			undefined
		);
		expect( result.current ).toHaveProperty( 'adsCurrencyConfig' );
		expect( result.current ).toMatchObject( {
			formatAmount: expect.any( Function ),
		} );
		expect( result.current.adsCurrencyConfig ).toEqual(
			expect.objectContaining( {
				code: expect.any( String ),
				precision: expect.any( Number ),
				symbol: expect.any( String ),
				symbolPosition: expect.any( String ),
				decimalSeparator: expect.any( String ),
				thousandSeparator: expect.any( String ),
				priceFormat: expect.any( String ),
			} )
		);
	} );

	test( "initially should return store's currency config w/ `code` and `symbol` set to empty", () => {
		const { result } = renderHook( () => useAdsCurrency() );
		const {
			result: { current: storesCurrencyConfig },
		} = renderHook( () => useStoreCurrency() );

		// assert initial state
		expect( result.current ).toHaveProperty( 'adsCurrencyConfig' );
		expect( result.current.adsCurrencyConfig ).toEqual( {
			...storesCurrencyConfig,
			code: '',
			symbol: '',
		} );
	} );

	describe( 'eventually', () => {
		let adsAccountData;
		beforeEach( () => {
			adsAccountData = {
				id: 777777,
				currency: 'PLN',
				symbol: 'zł',
				status: 'connected',
			};
			apiFetch
				// Mock /wp-json/wc/gla/jetpack/connected
				.mockReturnValueOnce( {
					active: 'yes',
					owner: 'yes',
					displayName: 'username',
					email: 'tomalec.gla1.test@gmail.com',
				} )
				// Mock /wp-json/wc/gla/google/connected
				.mockReturnValueOnce( {
					active: 'yes',
					email: 'gla1.test@example.com',
				} )
				// Mock /wp-json/wc/gla/ads/connection as a connected Ads account
				.mockReturnValueOnce( adsAccountData )
				// Mock /wp-json/wc/gla/ads/connection as a unconnected Ads account
				.mockReturnValueOnce( {
					id: 0,
					currency: null,
					symbol: null,
					status: 'disconnected',
				} );
		} );

		test( 'should finish resolution', async () => {
			const { result, waitFor } = renderHook( () => useAdsCurrency() );

			// assert initial state
			expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// assert eventual state
			await waitFor( () =>
				expect( result.current.hasFinishedResolution ).toEqual( true )
			);
		} );

		test( 'should return currency config with Ads account code and symbol', async () => {
			const { result, waitFor } = renderHook( () => useAdsCurrency() );

			// assert initial state
			// expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// Unfortunately, tests are not atomic, as the state is perserved between them.

			const initialConfig = result.current.adsCurrencyConfig;

			// Assert eventual value.
			await waitFor( () =>
				expect( result.current.adsCurrencyConfig ).toEqual( {
					...initialConfig,
					code: adsAccountData.currency,
					symbol: adsAccountData.symbol,
				} )
			);
		} );

		test( 'should be able to format value according to currency config', () => {
			const { result } = renderHook( () => useAdsCurrency() );
			const { formatAmount } = result.current;
			const value = 1234.5678;

			expect( formatAmount( value ) ).toBe( '1|234;568\xA0zł' );
			expect( formatAmount( value, true ) ).toBe( '1|234;568\xA0PLN' );
		} );

		test( 'when Ads account is not connected, it should return the code/symbol of currency config with fallback value', async () => {
			// During the test running, `@wordpress/data` will keep the store across all tests,
			// so the tests are not atomic, as the state is perserved between them.
			// Here we invalidate the `getGoogleAdsAccount` selector to force it to re-fetch its state.
			dispatch( STORE_KEY ).invalidateResolution(
				'getGoogleAdsAccount',
				[]
			);

			const { result, waitFor } = renderHook( () => useAdsCurrency() );

			await waitFor( () => {
				const { adsCurrencyConfig, formatAmount } = result.current;
				const value = 1234.5678;

				expect( adsCurrencyConfig.code ).toBe( '' );
				expect( adsCurrencyConfig.symbol ).toBe( '' );
				expect( formatAmount( value ) ).toBe( '1|234;568\xA0' );
				expect( formatAmount( value, true ) ).toBe( '1|234;568\xA0' );
			} );
		} );
	} );
} );

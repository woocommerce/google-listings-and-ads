/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
import { useAdsCurrencyConfig } from './useAdsCurrency';
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

describe( 'useAdsCurrencyConfig', () => {
	test( 'initially should return `{ currencyConfig: Object, hasFinishedResolution: undefined }`', () => {
		const { result } = renderHook( () => useAdsCurrencyConfig() );

		// assert initial state
		expect( result.current ).toHaveProperty(
			'hasFinishedResolution',
			undefined
		);
		expect( result.current ).toHaveProperty( 'currencyConfig' );
		expect( result.current.currencyConfig ).toEqual(
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

	test( "initially should return store's currencyConfig w/ `code` and `symbol` set to empty", () => {
		const { result } = renderHook( () => useAdsCurrencyConfig() );
		const {
			result: { current: storesCurrencyConfig },
		} = renderHook( () => useStoreCurrency() );

		// assert initial state
		expect( result.current ).toHaveProperty( 'currencyConfig' );
		expect( result.current.currencyConfig ).toEqual( {
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
				symbol: 'z\u0142',
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
				.mockReturnValueOnce(
					// Mock /wp-json/wc/gla/ads/connection
					adsAccountData
				);
		} );
		test( 'should finish resolution', async () => {
			const { result, waitFor } = renderHook( () =>
				useAdsCurrencyConfig()
			);

			// assert initial state
			expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// assert eventual state
			await waitFor( () =>
				expect( result.current.hasFinishedResolution ).toEqual( true )
			);
		} );

		test( 'should return currencyConfig with Ads account code and symbol', async () => {
			const { result, waitFor } = renderHook( () =>
				useAdsCurrencyConfig()
			);

			// assert initial state
			// expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// Unfortunately, tests are not atomic, as the state is perserved between them.

			const initialConfig = result.current.currencyConfig;

			// Assert eventual value.
			await waitFor( () =>
				expect( result.current.currencyConfig ).toEqual( {
					...initialConfig,
					code: adsAccountData.currency,
					symbol: adsAccountData.symbol,
				} )
			);
		} );
	} );
} );

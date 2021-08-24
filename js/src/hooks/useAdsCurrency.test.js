/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';
import { apiFetch } from '@wordpress/data-controls';

/**
 * Internal dependencies
 */
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
	test( 'initially should return `{ currency: Object, hasFinishedResolution: undefined }`', () => {
		const { result } = renderHook( () => useAdsCurrency() );

		// assert initial state
		expect( result.current ).toHaveProperty(
			'hasFinishedResolution',
			undefined
		);
		expect( result.current ).toHaveProperty( 'currency' );
		expect( result.current.currency ).toEqual(
			expect.objectContaining( {
				formatAmount: expect.any( Function ),
				getCurrencyConfig: expect.any( Function ),
			} )
		);
	} );

	test( "initially should return store's currency w/ `code` and `symbol` set to empty", () => {
		const { result } = renderHook( () => useAdsCurrency() );
		const {
			result: { current: storesCurrency },
		} = renderHook( () => useStoreCurrency() );

		// assert initial state
		expect( result.current ).toHaveProperty( 'currency.getCurrencyConfig' );
		expect( result.current.currency.getCurrencyConfig() ).toEqual( {
			...storesCurrency,
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
			const { result, waitFor } = renderHook( () => useAdsCurrency() );

			// assert initial state
			expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// assert eventual state
			await waitFor( () =>
				expect( result.current.hasFinishedResolution ).toEqual( true )
			);
		} );

		test( 'should return Ads account currency', async () => {
			const { result, waitFor } = renderHook( () => useAdsCurrency() );

			// assert initial state
			// expect( result.current.hasFinishedResolution ).toEqual( undefined );
			// Unfortunately, tests are not atomic, as the state is perserved between them.

			const initialConfig = result.current.currency.getCurrencyConfig();

			// Assert eventual value.
			await waitFor( () =>
				expect( result.current.currency.getCurrencyConfig() ).toEqual( {
					...initialConfig,
					code: adsAccountData.currency,
					symbol: adsAccountData.symbol,
				} )
			);
		} );
	} );
} );

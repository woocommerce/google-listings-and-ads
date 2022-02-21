/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react-hooks';

/**
 * Internal dependencies
 */
import useAdsCurrency from './useAdsCurrency';
import useStoreCurrency from './useStoreCurrency';
import useGoogleAdsAccount from './useGoogleAdsAccount';

const storeCurrencyConfig = {
	code: 'EUR',
	precision: 3,
	symbol: '€',
	symbolPosition: 'right_space',
	decimalSeparator: ';',
	thousandSeparator: '|',
	priceFormat: '%2$s %1$s',
};

jest.mock( './useStoreCurrency', () =>
	jest
		.fn()
		.mockName( 'useStoreCurrency' )
		.mockReturnValue( storeCurrencyConfig )
);

jest.mock( './useGoogleAdsAccount', () =>
	jest.fn().mockName( 'useGoogleAdsAccount' ).mockReturnValue( {} )
);

describe( 'useAdsCurrency', () => {
	test( 'initially should return `{ adsCurrencyConfig: Object, formatAmount: Function }`', () => {
		const { result } = renderHook( () => useAdsCurrency() );

		// assert initial state
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

	describe( 'after Google Ads Account status is fetched', () => {
		let connectedAdsAccount;
		let unconnectedAdsAccount;

		beforeEach( () => {
			connectedAdsAccount = {
				id: 777777,
				currency: 'PLN',
				symbol: 'zł',
				status: 'connected',
			};
			unconnectedAdsAccount = {
				id: 0,
				currency: null,
				symbol: null,
				status: 'disconnected',
			};
		} );

		test( 'should return currency config with Ads account code and symbol', async () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: connectedAdsAccount,
			} );

			const { result } = renderHook( () => useAdsCurrency() );

			expect( result.current.adsCurrencyConfig ).toEqual( {
				...storeCurrencyConfig,
				code: connectedAdsAccount.currency,
				symbol: connectedAdsAccount.symbol,
			} );
		} );

		test( 'should be able to format value according to currency config', () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: connectedAdsAccount,
			} );

			const { result } = renderHook( () => useAdsCurrency() );
			const { formatAmount } = result.current;
			const value = 1234.5678;

			expect( formatAmount( value ) ).toBe( '1|234;568\xA0zł' );
			expect( formatAmount( value, true ) ).toBe( '1|234;568\xA0PLN' );
		} );

		test( 'when Ads account is not connected, it should return the code/symbol of currency config with fallback value', async () => {
			useGoogleAdsAccount.mockReturnValue( {
				googleAdsAccount: unconnectedAdsAccount,
			} );

			const { result } = renderHook( () => useAdsCurrency() );

			const { adsCurrencyConfig, formatAmount } = result.current;
			const value = 1234.5678;

			expect( adsCurrencyConfig.code ).toBe( '' );
			expect( adsCurrencyConfig.symbol ).toBe( '' );
			expect( formatAmount( value ) ).toBe( '1|234;568\xA0' );
			expect( formatAmount( value, true ) ).toBe( '1|234;568\xA0' );
		} );
	} );
} );

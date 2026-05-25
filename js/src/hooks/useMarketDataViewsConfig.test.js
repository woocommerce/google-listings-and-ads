/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMarketDataViewsConfig from './useMarketDataViewsConfig';
import useMarkets from '~/hooks/useMarkets';
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useSettings from '~/hooks/useSettings';
import useShippingRates from '~/hooks/useShippingRates';
import useShippingTimes from '~/hooks/useShippingTimes';
import useAdsCurrency from '~/hooks/useAdsCurrency';
import useMarketCurrency from '~/hooks/useMarketCurrency';

jest.mock( '~/hooks/useMarkets' );
jest.mock( '~/hooks/usePrimaryMarketDetails' );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/hooks/useSettings' );
jest.mock( '~/hooks/useShippingRates' );
jest.mock( '~/hooks/useShippingTimes' );
jest.mock( '~/hooks/useAdsCurrency' );
jest.mock( '~/hooks/useMarketCurrency' );

const SHIPPING_RATES = [
	{ id: 1, country: 'US', currency: 'USD', rate: 10, options: {} },
	{
		id: 2,
		country: 'FR',
		currency: 'EUR',
		rate: 8,
		options: { free_shipping_threshold: 50 },
	},
];

const SHIPPING_TIMES = [
	{ countryCode: 'US', time: 3, maxTime: 5 },
	{ countryCode: 'FR', time: 5, maxTime: 7 },
];

const PRIMARY_MARKET = {
	id: 'primary',
	label: 'Primary Market',
	countries: [ 'US', 'CA', 'MX' ],
	country: 'US',
	shipping_rate: 'manual',
	shipping_time: 'manual',
};

const PRIMARY_MARKET_FLAT = {
	...PRIMARY_MARKET,
	currency: 'USD',
	shipping_rate: 'flat',
	shipping_time: 'flat',
	free_shipping: null,
};

const SECONDARY_MARKET = {
	id: 'fr',
	country: 'FR',
	shipping_rate: 'flat',
	shipping_time: 'flat',
};

const SECONDARY_MARKET_FLAT = {
	id: 'fr',
	country: 'FR',
	currency: 'EUR',
	shipping_rate: 'flat',
	shipping_time: 'flat',
	free_shipping: 50,
};

const PRIMARY_MARKET_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
};

const PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
	language: 'English',
	currency: 'USD',
};

const SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	id: 'fr',
	country: 'FR',
	shipping_rate: 'automatic',
	language: 'French',
	currency: 'EUR',
};

const PRIMARY_MARKET_MULTILINGUAL_MANUAL = {
	...PRIMARY_MARKET,
	shipping_rate: 'manual',
	language: 'English',
	currency: 'USD',
};

const SECONDARY_MARKET_MULTILINGUAL_MANUAL = {
	id: 'fr',
	country: 'FR',
	label: 'France',
	shipping_rate: 'manual',
	language: 'French',
	currency: 'EUR',
};

/**
 * Simple stand-in for useAdsCurrency.formatAmount used in non-multilingual
 * scenarios. Prefixes the raw amount with "ADS:" so tests can assert which
 * formatter path was taken without caring about real currency symbols.
 *
 * @param {number} amount
 * @return {string} "ADS:<amount>"
 */
const adsFormatAmount = ( amount ) => `ADS:${ amount }`;

/**
 * Simple stand-in for useMarketCurrency.formatAmount used in multilingual
 * scenarios. Encodes both the currency code and amount so tests can verify
 * that the correct currency is forwarded per market row.
 *
 * @param {number} amount
 * @param {string} currencyCode
 * @return {string} "MKT:<currencyCode>:<amount>"
 */
const marketFormatAmount = ( amount, currencyCode ) =>
	`MKT:${ currencyCode }:${ amount }`;

const setMocks = ( {
	primary = PRIMARY_MARKET,
	markets = [ PRIMARY_MARKET ],
	countries = {
		US: 'United States',
		CA: 'Canada',
		MX: 'Mexico',
		FR: 'France',
	},
	multiLingualStore = false,
	shippingRate = primary.shipping_rate,
	shippingRates = [],
	shippingTimes = [],
} = {} ) => {
	useMarkets.mockReturnValue( {
		data: markets,
		hasFinishedResolution: true,
	} );
	usePrimaryMarketDetails.mockReturnValue( {
		data: primary,
		hasFinishedResolution: true,
	} );
	useCountryKeyNameMap.mockReturnValue( countries );
	useSettings.mockReturnValue( {
		settings: { shipping_rate: shippingRate },
	} );
	useShippingRates.mockReturnValue( {
		data: shippingRates,
		hasFinishedResolution: true,
	} );
	useShippingTimes.mockReturnValue( {
		data: shippingTimes,
		hasFinishedResolution: true,
	} );
	useAdsCurrency.mockReturnValue( {
		adsCurrencyConfig: {},
		formatAmount: adsFormatAmount,
	} );
	useMarketCurrency.mockReturnValue( {
		formatAmount: marketFormatAmount,
	} );
	// `glaData` is captured as a reference to `window.glaData` at module load
	// (see `js/src/constants.js`), so mutate in place rather than replacing the
	// object — replacing would leave the original reference stale.
	window.glaData.isMultiLingualStore = multiLingualStore;
};

describe( 'useMarketDataViewsConfig', () => {
	afterEach( () => {
		useMarkets.mockReset();
		usePrimaryMarketDetails.mockReset();
		useCountryKeyNameMap.mockReset();
		useSettings.mockReset();
		useShippingRates.mockReset();
		useShippingTimes.mockReset();
		useAdsCurrency.mockReset();
		useMarketCurrency.mockReset();
		delete window.glaData.isMultiLingualStore;
	} );

	describe( 'manual shipping, no multilingual store', () => {
		test( 'returns exactly three fields: Market, Country, Shipping', () => {
			setMocks();

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'country',
				'shipping',
			] );
		} );

		test( 'returns only the primary market row', () => {
			setMocks( {
				markets: [ PRIMARY_MARKET, SECONDARY_MARKET ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 1 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
		} );

		test( 'formats the country cell as "<n> countries"', () => {
			setMocks();

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].country ).toBe( '3 countries' );
		} );

		test( 'pluralizes singular country count', () => {
			setMocks( {
				primary: { ...PRIMARY_MARKET, countries: [ 'US' ] },
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].country ).toBe( '1 country' );
		} );

		test( 'sets the shipping cell to the static "Managed in Google" string', () => {
			setMocks();

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].shipping ).toBe(
				'Managed in Google'
			);
		} );

		test( 'sets the market cell to the primary market label', () => {
			setMocks();

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe( 'Primary Market' );
		} );
	} );

	describe( 'fall-through (any other scenario)', () => {
		test( 'returns the legacy two-column shape for an unrecognised shipping rate', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
				shippingRate: null,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'shippingTime',
			] );
		} );

		test( 'returns every market when in fall-through', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
				shippingRate: null,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
		} );

		test( 'formats primary as "<label> (N countries)"', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT ],
				shippingRate: null,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );

		test( 'formats non-primary as the country name from the lookup', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
				shippingRate: null,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].label ).toBe( 'France' );
		} );
	} );

	describe( 'flat shipping, no multilingual store', () => {
		test( 'returns exactly four fields: Market, Shipping rate, Shipping time, Free shipping', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'shippingRate',
				'shippingTime',
				'freeShipping',
			] );
		} );

		test( 'includes both primary and additional markets as rows', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
			expect( result.current.data[ 1 ].id ).toBe( 'fr' );
		} );

		test( 'retains shippingRate and shippingTime from each market', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				shippingRates: SHIPPING_RATES,
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.shippingRate ).toMatch( /10/ );
			expect( primary.shippingTime ).toBe( '3 - 5 days' );
			expect( secondary.shippingRate ).toMatch( /8/ );
			expect( secondary.shippingTime ).toBe( '5 - 7 days' );
		} );

		test( 'formats amounts using useAdsCurrency (non-multilingual)', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				shippingRates: SHIPPING_RATES,
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			// adsFormatAmount prefixes with "ADS:" — confirms the Ads-account
			// currency formatter is used, not the per-market one.
			expect( primary.shippingRate ).toBe( 'ADS:10' );
			expect( secondary.shippingRate ).toBe( 'ADS:8' );
			expect( secondary.freeShipping ).toBe( 'Free over ADS:50' );
		} );

		test( 'sets freeShipping to a dash when free_shipping is null', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].freeShipping ).toBe( '-' );
		} );

		test( 'formats freeShipping as "Free over <amount>" when free_shipping is set', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				shippingRates: SHIPPING_RATES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].freeShipping ).toMatch(
				/Free over/
			);
			expect( result.current.data[ 1 ].freeShipping ).toMatch( /50/ );
		} );

		test( 'formats the primary market label with the country count', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );

		test( 'leaves the secondary market label as the country name', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [
					PRIMARY_MARKET_FLAT,
					{ ...SECONDARY_MARKET_FLAT, label: 'France' },
				],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].label ).toBe( 'France' );
		} );
	} );

	describe( 'automatic shipping, no multilingual store', () => {
		test( 'returns exactly two fields: Market, Shipping time', () => {
			setMocks( {
				primary: PRIMARY_MARKET_AUTOMATIC,
				markets: [ PRIMARY_MARKET_AUTOMATIC ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'shippingTime',
			] );
		} );

		test( 'returns only the primary market row', () => {
			setMocks( {
				primary: PRIMARY_MARKET_AUTOMATIC,
				markets: [ PRIMARY_MARKET_AUTOMATIC, SECONDARY_MARKET ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 1 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
		} );

		test( 'formats the market label as "<label> (N countries)"', () => {
			setMocks( {
				primary: PRIMARY_MARKET_AUTOMATIC,
				markets: [ PRIMARY_MARKET_AUTOMATIC ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );

		test( 'pluralizes singular country count', () => {
			setMocks( {
				primary: {
					...PRIMARY_MARKET_AUTOMATIC,
					countries: [ 'US' ],
				},
				markets: [ PRIMARY_MARKET_AUTOMATIC ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (1 country)'
			);
		} );

		test( 'retains shippingTime from primary market data', () => {
			setMocks( {
				primary: PRIMARY_MARKET_AUTOMATIC,
				markets: [ PRIMARY_MARKET_AUTOMATIC ],
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].shippingTime ).toBe(
				'3 - 5 days'
			);
		} );
	} );

	describe( 'multilingual store, automatic shipping', () => {
		test( 'returns exactly four fields: Market, Language, Currency, Shipping time', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
				markets: [ PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'language',
				'currency',
				'shippingTime',
			] );
		} );

		test( 'includes both primary and additional markets as rows', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
					SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
			expect( result.current.data[ 1 ].id ).toBe( 'fr' );
		} );

		test( 'each row retains market language, currency, and shippingTime', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
					SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC,
				],
				multiLingualStore: true,
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.language ).toBe( 'English' );
			expect( primary.currency ).toBe( 'USD' );
			expect( primary.shippingTime ).toBe( '3 - 5 days' );
			expect( secondary.language ).toBe( 'French' );
			expect( secondary.currency ).toBe( 'EUR' );
			expect( secondary.shippingTime ).toBe( '5 - 7 days' );
		} );

		test( 'formats the primary market label with the country count', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
					SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );
	} );

	describe( 'multilingual store, manual shipping', () => {
		test( 'returns exactly three fields: Market, Language, Currency', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_MANUAL,
				markets: [ PRIMARY_MARKET_MULTILINGUAL_MANUAL ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'language',
				'currency',
			] );
		} );

		test( 'includes both primary and additional markets as rows', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_MANUAL,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_MANUAL,
					SECONDARY_MARKET_MULTILINGUAL_MANUAL,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
			expect( result.current.data[ 1 ].id ).toBe( 'fr' );
		} );

		test( 'formats the primary market label with the country count', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_MANUAL,
				markets: [ PRIMARY_MARKET_MULTILINGUAL_MANUAL ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );

		test( 'pluralizes singular country count', () => {
			setMocks( {
				primary: {
					...PRIMARY_MARKET_MULTILINGUAL_MANUAL,
					countries: [ 'US' ],
				},
				markets: [
					{
						...PRIMARY_MARKET_MULTILINGUAL_MANUAL,
						countries: [ 'US' ],
					},
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (1 country)'
			);
		} );

		test( 'leaves the secondary market label as the country name', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_MANUAL,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_MANUAL,
					SECONDARY_MARKET_MULTILINGUAL_MANUAL,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].label ).toBe( 'France' );
		} );

		test( 'each row retains market language and currency', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_MANUAL,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_MANUAL,
					SECONDARY_MARKET_MULTILINGUAL_MANUAL,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.language ).toBe( 'English' );
			expect( primary.currency ).toBe( 'USD' );
			expect( secondary.language ).toBe( 'French' );
			expect( secondary.currency ).toBe( 'EUR' );
		} );
	} );

	describe( 'multilingual store, flat shipping', () => {
		// Per Figma the multilingual flat table uses the same four columns as
		// the non-multilingual flat table (no extra Language/Currency columns).
		// However the two scenarios diverge in *how* amounts are formatted:
		// non-multilingual uses useAdsCurrency (single Ads-account currency),
		// while multilingual uses useMarketCurrency so each market row is
		// formatted in its own currency.

		test( 'returns the same four fields as the non-multilingual flat scenario', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'shippingRate',
				'shippingTime',
				'freeShipping',
			] );
		} );

		test( 'includes both primary and additional markets as rows', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
			expect( result.current.data[ 1 ].id ).toBe( 'fr' );
		} );

		test( 'formats shipping rate and time for each market row', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
				shippingRates: SHIPPING_RATES,
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.shippingRate ).toMatch( /10/ );
			expect( primary.shippingTime ).toBe( '3 - 5 days' );
			expect( secondary.shippingRate ).toMatch( /8/ );
			expect( secondary.shippingTime ).toBe( '5 - 7 days' );
			expect( secondary.freeShipping ).toMatch( /Free over/ );
		} );

		test( 'formats amounts using useMarketCurrency with per-market currency codes', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
				shippingRates: SHIPPING_RATES,
				shippingTimes: SHIPPING_TIMES,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			// marketFormatAmount encodes both amount and currency code so we can
			// assert each row was formatted with the correct market currency.
			expect( primary.shippingRate ).toBe( 'MKT:USD:10' );
			expect( secondary.shippingRate ).toBe( 'MKT:EUR:8' );
			expect( secondary.freeShipping ).toBe( 'Free over MKT:EUR:50' );
		} );

		test( 'formats the primary market label with the country count', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (3 countries)'
			);
		} );
	} );

	describe( 'loading state', () => {
		test( 'returns empty fields and data while markets have not resolved', () => {
			useMarkets.mockReturnValue( {
				data: [],
				hasFinishedResolution: false,
			} );
			usePrimaryMarketDetails.mockReturnValue( {
				data: null,
				hasFinishedResolution: false,
			} );
			useCountryKeyNameMap.mockReturnValue( {} );
			useSettings.mockReturnValue( { settings: null } );
			useShippingRates.mockReturnValue( {
				data: [],
				hasFinishedResolution: false,
			} );
			useShippingTimes.mockReturnValue( {
				data: [],
				hasFinishedResolution: false,
			} );
			useAdsCurrency.mockReturnValue( {
				adsCurrencyConfig: {},
				formatAmount: adsFormatAmount,
			} );
			useMarketCurrency.mockReturnValue( {
				formatAmount: marketFormatAmount,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields ).toEqual( [] );
			expect( result.current.data ).toEqual( [] );
			expect( result.current.hasFinishedResolution ).toBe( false );
		} );

		test( 'returns empty fields and data when markets are resolved but settings are not yet available', () => {
			useMarkets.mockReturnValue( {
				data: [ PRIMARY_MARKET ],
				hasFinishedResolution: true,
			} );
			usePrimaryMarketDetails.mockReturnValue( {
				data: PRIMARY_MARKET,
				hasFinishedResolution: true,
			} );
			useCountryKeyNameMap.mockReturnValue( {} );
			useSettings.mockReturnValue( { settings: undefined } );
			useShippingRates.mockReturnValue( {
				data: [],
				hasFinishedResolution: true,
			} );
			useShippingTimes.mockReturnValue( {
				data: [],
				hasFinishedResolution: true,
			} );
			useAdsCurrency.mockReturnValue( {
				adsCurrencyConfig: {},
				formatAmount: adsFormatAmount,
			} );
			useMarketCurrency.mockReturnValue( {
				formatAmount: marketFormatAmount,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields ).toEqual( [] );
			expect( result.current.data ).toEqual( [] );
			expect( result.current.hasFinishedResolution ).toBe( false );
		} );
	} );

	describe( 'reactivity', () => {
		test( 'data reflects updates from useMarkets', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT ],
			} );

			const { result, rerender } = renderHook( () =>
				useMarketDataViewsConfig()
			);
			expect( result.current.data ).toHaveLength( 1 );

			useMarkets.mockReturnValue( {
				data: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
				hasFinishedResolution: true,
			} );
			rerender();

			expect( result.current.data ).toHaveLength( 2 );
		} );
	} );
} );

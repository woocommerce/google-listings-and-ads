/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMarketDataViewsConfig from './useMarketDataViewsConfig';
import useMarkets from './useMarkets';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';
import useSettings from '~/hooks/useSettings';
jest.mock( './useMarkets' );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/hooks/useSettings' );

const FLAT_SHIPPING = {
	rate_type: 'flat',
	time_type: 'flat',
	flat_rate: 10,
	free_shipping_threshold: null,
	flat_time: 3,
	flat_max_time: 5,
};

const PRIMARY_MARKET = {
	id: 'primary',
	label: 'Primary Market',
	countries: [ 'US', 'CA', 'MX' ],
	country: null,
	shipping_rate: 'manual',
	shipping_time: 'manual',
};

const PRIMARY_MARKET_FLAT = {
	...PRIMARY_MARKET,
	currency: 'USD',
	shipping_rate: 'flat',
	shipping_time: 'flat',
	free_shipping: null,
	shipping: FLAT_SHIPPING,
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
	shipping: {
		rate_type: 'flat',
		time_type: 'flat',
		flat_rate: 8,
		free_shipping_threshold: 50,
		flat_time: 5,
		flat_max_time: 7,
	},
};

const PRIMARY_MARKET_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
};

const PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
	language: [ 'en' ],
	currency: [ 'USD' ],
	shipping: { ...FLAT_SHIPPING, rate_type: 'automatic' },
};

const SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	id: 'fr',
	country: 'FR',
	shipping_rate: 'automatic',
	language: [ 'fr' ],
	currency: [ 'EUR' ],
	shipping: {
		rate_type: 'automatic',
		time_type: 'flat',
		flat_rate: null,
		free_shipping_threshold: null,
		flat_time: 5,
		flat_max_time: 7,
	},
};

const PRIMARY_MARKET_MULTILINGUAL_MANUAL = {
	...PRIMARY_MARKET,
	shipping_rate: 'manual',
	language: [ 'en' ],
	currency: [ 'USD' ],
};

const SECONDARY_MARKET_MULTILINGUAL_MANUAL = {
	id: 'fr',
	country: 'FR',
	label: 'France',
	shipping_rate: 'manual',
	language: [ 'fr' ],
	currency: [ 'EUR' ],
};

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
} = {} ) => {
	useMarkets.mockReturnValue( {
		data: markets,
		hasFinishedResolution: true,
	} );
	useCountryKeyNameMap.mockReturnValue( countries );
	useSettings.mockReturnValue( {
		settings: { shipping_rate: shippingRate },
	} );
	// `glaData` is captured as a reference to `window.glaData` at module load
	// (see `js/src/constants.js`), so mutate in place rather than replacing the
	// object — replacing would leave the original reference stale.
	window.glaData.isMultiLingualStore = multiLingualStore;
};

describe( 'useMarketDataViewsConfig', () => {
	afterEach( () => {
		useMarkets.mockReset();
		useCountryKeyNameMap.mockReset();
		useSettings.mockReset();
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
				markets: [ { ...PRIMARY_MARKET, countries: [ 'US' ] } ],
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

		test( "reads each row's shipping straight from the market's own shipping object", () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.shipping ).toMatchObject( {
				flat_rate: 10,
				flat_time: 3,
				flat_max_time: 5,
			} );
			expect( secondary.shipping ).toMatchObject( {
				flat_rate: 8,
				flat_time: 5,
				flat_max_time: 7,
				free_shipping_threshold: 50,
			} );
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

		test( 'returns all markets as rows', () => {
			setMocks( {
				primary: PRIMARY_MARKET_AUTOMATIC,
				markets: [ PRIMARY_MARKET_AUTOMATIC, SECONDARY_MARKET ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
			expect( result.current.data[ 0 ].id ).toBe( 'primary' );
			expect( result.current.data[ 1 ].id ).toBe( 'fr' );
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
			const singleCountryMarket = {
				...PRIMARY_MARKET_AUTOMATIC,
				countries: [ 'US' ],
			};
			setMocks( {
				primary: singleCountryMarket,
				markets: [ singleCountryMarket ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].label ).toBe(
				'Primary Market (1 country)'
			);
		} );

		test( "reads the row's shipping straight from the market's own shipping object", () => {
			setMocks( {
				primary: {
					...PRIMARY_MARKET_AUTOMATIC,
					shipping: FLAT_SHIPPING,
				},
				markets: [
					{ ...PRIMARY_MARKET_AUTOMATIC, shipping: FLAT_SHIPPING },
				],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].shipping ).toMatchObject( {
				flat_time: 3,
				flat_max_time: 5,
			} );
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

		test( 'each row retains market language, currency, and its own shipping object', () => {
			setMocks( {
				primary: PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
				markets: [
					PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC,
					SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC,
				],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.language ).toEqual( [ 'en' ] );
			expect( primary.currency ).toEqual( [ 'USD' ] );
			expect( primary.shipping ).toMatchObject( {
				flat_time: 3,
				flat_max_time: 5,
			} );
			expect( secondary.language ).toEqual( [ 'fr' ] );
			expect( secondary.currency ).toEqual( [ 'EUR' ] );
			expect( secondary.shipping ).toMatchObject( {
				flat_time: 5,
				flat_max_time: 7,
			} );
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

			expect( primary.language ).toEqual( [ 'en' ] );
			expect( primary.currency ).toEqual( [ 'USD' ] );
			expect( secondary.language ).toEqual( [ 'fr' ] );
			expect( secondary.currency ).toEqual( [ 'EUR' ] );
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

		test( "reads each row's shipping straight from the market's own shipping object", () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET_FLAT ],
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.shipping ).toMatchObject( {
				flat_rate: 10,
				flat_time: 3,
				flat_max_time: 5,
			} );
			expect( secondary.shipping ).toMatchObject( {
				flat_rate: 8,
				flat_time: 5,
				flat_max_time: 7,
				free_shipping_threshold: 50,
			} );
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
			useCountryKeyNameMap.mockReturnValue( {} );
			useSettings.mockReturnValue( { settings: null } );

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
			useCountryKeyNameMap.mockReturnValue( {} );
			useSettings.mockReturnValue( { settings: undefined } );

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

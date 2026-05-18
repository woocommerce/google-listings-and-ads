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

jest.mock( '~/hooks/useMarkets' );
jest.mock( '~/hooks/usePrimaryMarketDetails' );
jest.mock( '~/hooks/useCountryKeyNameMap' );
jest.mock( '~/hooks/useSettings' );

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
	shippingRate: 10,
	shippingTime: '3-5 days',
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
	shippingRate: 8,
	shippingTime: '5-7 days',
	free_shipping: 50,
};

const PRIMARY_MARKET_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
	shippingTime: '3-5 days',
};

const PRIMARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	...PRIMARY_MARKET,
	shipping_rate: 'automatic',
	language: 'English',
	currency: 'USD',
	shippingTime: '3-5 days',
};

const SECONDARY_MARKET_MULTILINGUAL_AUTOMATIC = {
	id: 'fr',
	country: 'FR',
	shipping_rate: 'automatic',
	language: 'French',
	currency: 'EUR',
	shippingTime: '5-7 days',
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
	usePrimaryMarketDetails.mockReturnValue( {
		data: primary,
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
		usePrimaryMarketDetails.mockReset();
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
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.shippingRate ).toBe( 10 );
			expect( primary.shippingTime ).toBe( '3-5 days' );
			expect( secondary.shippingRate ).toBe( 8 );
			expect( secondary.shippingTime ).toBe( '5-7 days' );
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
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].freeShipping ).toMatch(
				/Free over/
			);
			expect( result.current.data[ 1 ].freeShipping ).toMatch( /50/ );
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
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].shippingTime ).toBe( '3-5 days' );
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
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );
			const [ primary, secondary ] = result.current.data;

			expect( primary.language ).toBe( 'English' );
			expect( primary.currency ).toBe( 'USD' );
			expect( primary.shippingTime ).toBe( '3-5 days' );
			expect( secondary.language ).toBe( 'French' );
			expect( secondary.currency ).toBe( 'EUR' );
			expect( secondary.shippingTime ).toBe( '5-7 days' );
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

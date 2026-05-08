/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useMarketDataViewsConfig from './use-market-data-views-config';
import useMarkets from '~/hooks/useMarkets';
import usePrimaryMarketDetails from '~/hooks/usePrimaryMarketDetails';
import useCountryKeyNameMap from '~/hooks/useCountryKeyNameMap';

jest.mock( '~/hooks/useMarkets' );
jest.mock( '~/hooks/usePrimaryMarketDetails' );
jest.mock( '~/hooks/useCountryKeyNameMap' );

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
	shipping_rate: 'flat',
	shipping_time: 'flat',
};

const SECONDARY_MARKET = {
	id: 'fr',
	country: 'FR',
	shipping_rate: 'flat',
	shipping_time: 'flat',
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
	// `glaData` is captured as a reference to `window.glaData` at module load
	// (see `js/src/constants.js`), so mutate in place rather than replacing the
	// object — replacing would leave the original reference stale.
	window.glaData.multiLingualStore = multiLingualStore;
};

describe( 'useMarketDataViewsConfig', () => {
	afterEach( () => {
		useMarkets.mockReset();
		usePrimaryMarketDetails.mockReset();
		useCountryKeyNameMap.mockReset();
		delete window.glaData.multiLingualStore;
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

			expect( result.current.data[ 0 ].market ).toBe( 'Primary Market' );
		} );
	} );

	describe( 'fall-through (any other scenario)', () => {
		test( 'returns the legacy two-column shape for flat shipping', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
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
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data ).toHaveLength( 2 );
		} );

		test( 'formats primary as "<label> (N countries)"', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 0 ].market ).toBe(
				'Primary Market (3 countries)'
			);
		} );

		test( 'formats non-primary as the country name from the lookup', () => {
			setMocks( {
				primary: PRIMARY_MARKET_FLAT,
				markets: [ PRIMARY_MARKET_FLAT, SECONDARY_MARKET ],
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.data[ 1 ].market ).toBe( 'France' );
		} );

		test( 'falls through when multiLingualStore is true even with manual shipping', () => {
			setMocks( {
				multiLingualStore: true,
			} );

			const { result } = renderHook( () => useMarketDataViewsConfig() );

			expect( result.current.fields.map( ( f ) => f.id ) ).toEqual( [
				'market',
				'shippingTime',
			] );
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

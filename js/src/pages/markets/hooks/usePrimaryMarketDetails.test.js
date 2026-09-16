/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { PRIMARY_MARKET_ID } from '../constants';
import usePrimaryMarketDetails from './usePrimaryMarketDetails';
import useMarkets from './useMarkets';

jest.mock( './useMarkets' );

describe( 'usePrimaryMarketDetails', () => {
	afterEach( () => {
		useMarkets.mockReset();
	} );

	test( 'returns the primary market from the markets list', () => {
		const primary = {
			id: PRIMARY_MARKET_ID,
			label: 'Primary Market',
			countries: [ 'US', 'CA' ],
			shipping_rate: 'manual',
		};

		useMarkets.mockReturnValue( {
			data: [
				primary,
				{ id: 'fr', country: 'FR', shipping_rate: 'flat' },
			],
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => usePrimaryMarketDetails() );

		expect( result.current.data ).toBe( primary );
		expect( result.current.hasFinishedResolution ).toBe( true );
	} );

	test( 'returns undefined data while resolution is still in flight', () => {
		useMarkets.mockReturnValue( {
			data: [],
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => usePrimaryMarketDetails() );

		expect( result.current.data ).toBeUndefined();
		expect( result.current.hasFinishedResolution ).toBe( false );
	} );

	test( 'returns undefined data when no primary entry is present', () => {
		useMarkets.mockReturnValue( {
			data: [ { id: 'fr', country: 'FR' } ],
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => usePrimaryMarketDetails() );

		expect( result.current.data ).toBeUndefined();
	} );
} );

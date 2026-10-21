/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { resolveSelect, select } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { useAppDispatch, STORE_KEY } from '~/data';

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

describe( 'saveSettings', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockImplementation( ( { path } ) => {
			if ( path === '/wc/gla/mc/markets' ) {
				return Promise.resolve( [] );
			}
			if ( path === '/wc/gla/mc/target_audience' ) {
				return Promise.resolve( { locations: [], location: 'all' } );
			}
			return Promise.resolve( {} );
		} );
	} );

	it( 'invalidates the markets and target audience resolutions after saving', async () => {
		// Resolve markets and target audience once, so their resolution
		// state is populated before saving settings.
		await resolveSelect( STORE_KEY ).getMarkets();
		await resolveSelect( STORE_KEY ).getTargetAudience();

		expect(
			select( STORE_KEY ).hasFinishedResolution( 'getMarkets', [] )
		).toBe( true );
		expect(
			select( STORE_KEY ).hasFinishedResolution( 'getTargetAudience', [] )
		).toBe( true );

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.saveSettings( { shipping_rate: 'automatic' } );

		expect(
			select( STORE_KEY ).hasFinishedResolution( 'getMarkets', [] )
		).toBe( false );
		expect(
			select( STORE_KEY ).hasFinishedResolution( 'getTargetAudience', [] )
		).toBe( false );
	} );
} );

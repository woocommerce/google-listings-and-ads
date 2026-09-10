/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import apiFetch from '@wordpress/api-fetch';
import { getQuery, getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import { API_NAMESPACE } from '~/data/constants';

jest.mock( '@woocommerce/navigation' );

jest.mock( '@wordpress/api-fetch', () => {
	const impl = jest.fn().mockName( '@wordpress/api-fetch' );
	impl.use = jest.fn().mockName( 'apiFetch.use' );
	return impl;
} );

describe( 'fetchGoogleSearchConsoleAccount', () => {
	const mockFetch = jest.fn().mockResolvedValue( { status: 'connected' } );

	beforeEach( () => {
		jest.clearAllMocks();
		apiFetch.mockImplementation( ( args ) => mockFetch( args ) );
		getQuery.mockReturnValue( {} );
		getHistory.mockReturnValue( { replace: jest.fn() } );
	} );

	it( 'fetches the plain connection status when not returning from an OAuth redirect', async () => {
		const { result } = renderHook( () => useAppDispatch() );

		await result.current.fetchGoogleSearchConsoleAccount();

		expect( mockFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/search-console/connection`,
		} );
		expect( getHistory ).not.toHaveBeenCalled();
	} );

	it( 'confirms the reconnect and cleans up the query string when Woo confirms the OAuth redirect succeeded', async () => {
		getQuery.mockReturnValue( { 'google-mc': 'connected' } );
		const replace = jest.fn();
		getHistory.mockReturnValue( { replace } );

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.fetchGoogleSearchConsoleAccount();

		expect( mockFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/search-console/connection?confirm_reconnect=true`,
		} );
		expect( replace ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'does not confirm the reconnect when the OAuth redirect reports cancellation', async () => {
		getQuery.mockReturnValue( { google: 'cancelled' } );

		const { result } = renderHook( () => useAppDispatch() );

		await result.current.fetchGoogleSearchConsoleAccount();

		expect( mockFetch ).toHaveBeenCalledWith( {
			path: `${ API_NAMESPACE }/search-console/connection`,
		} );
		expect( getHistory ).not.toHaveBeenCalled();
	} );
} );

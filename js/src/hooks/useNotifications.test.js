/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useNotifications from './useNotifications';
import { STORE_KEY } from '~/data/constants';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useNotifications', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'calls getNotifications on the store and returns the result', () => {
		const notifications = [
			{ id: 'notif-1', triggered_at: 1000 },
			{ id: 'notif-2', triggered_at: 2000 },
		];

		const getNotifications = jest.fn().mockReturnValue( notifications );
		const select = jest.fn().mockReturnValue( { getNotifications } );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useNotifications() );

		expect( select ).toHaveBeenCalledWith( STORE_KEY );
		expect( getNotifications ).toHaveBeenCalledTimes( 1 );
		expect( result.current ).toEqual( notifications );
	} );

	it( 'returns empty array when the store returns an empty list', () => {
		const getNotifications = jest.fn().mockReturnValue( [] );
		const select = jest.fn().mockReturnValue( { getNotifications } );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useNotifications() );

		expect( result.current ).toEqual( [] );
	} );
} );

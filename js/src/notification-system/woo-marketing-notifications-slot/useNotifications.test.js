/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useNotifications from './useNotifications';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

const STORE_NAME = 'woocommerce/marketing-notifications-system';

describe( 'useNotifications', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'calls getNotifications on the shared store and returns the result', () => {
		const notifications = [
			{ triggeredAt: 2000, component: () => null },
			{ triggeredAt: 1000, component: () => null },
		];

		const getNotifications = jest.fn().mockReturnValue( notifications );
		const select = jest.fn().mockReturnValue( { getNotifications } );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useNotifications() );

		expect( select ).toHaveBeenCalledWith( STORE_NAME );
		expect( getNotifications ).toHaveBeenCalledTimes( 1 );
		expect( result.current ).toEqual( notifications );
	} );

	it( 'returns an empty array when the store has no notifications', () => {
		const getNotifications = jest.fn().mockReturnValue( [] );
		const select = jest.fn().mockReturnValue( { getNotifications } );

		useSelect.mockImplementation( ( cb ) => cb( select ) );

		const { result } = renderHook( () => useNotifications() );

		expect( result.current ).toEqual( [] );
	} );
} );

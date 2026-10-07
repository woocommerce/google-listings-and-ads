/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleBusinessProfileConnection from './useGoogleBusinessProfileConnection';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useGoogleBusinessProfileConnection', () => {
	it( 'returns the connection and whether it has resolved', () => {
		const connection = { status: 'connected', location: null };
		const getGoogleBusinessProfileConnection = jest.fn( () => {
			return connection;
		} );
		const hasFinishedResolution = jest.fn( () => {
			return true;
		} );

		useSelect.mockImplementation( ( callback ) => {
			return callback( () => {
				return {
					getGoogleBusinessProfileConnection,
					hasFinishedResolution,
				};
			} );
		} );

		const { result } = renderHook( () =>
			useGoogleBusinessProfileConnection()
		);

		expect( result.current ).toEqual( {
			connection,
			hasFinishedResolution: true,
		} );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleBusinessProfileConnection',
			[]
		);
	} );
} );

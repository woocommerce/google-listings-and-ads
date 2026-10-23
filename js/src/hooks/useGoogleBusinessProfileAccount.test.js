/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleBusinessProfileAccount from './useGoogleBusinessProfileAccount';

jest.mock( '@wordpress/data', () => ( {
	useSelect: jest.fn(),
} ) );

describe( 'useGoogleBusinessProfileAccount', () => {
	it( 'returns the connection and whether it has resolved', () => {
		const account = { status: 'connected', id: '1111' };
		const getGoogleBusinessProfileAccount = jest.fn( () => {
			return account;
		} );
		const hasFinishedResolution = jest.fn( () => {
			return true;
		} );

		useSelect.mockImplementation( ( callback ) => {
			return callback( () => {
				return {
					getGoogleBusinessProfileAccount,
					hasFinishedResolution,
				};
			} );
		} );

		const { result } = renderHook( () =>
			useGoogleBusinessProfileAccount()
		);

		expect( result.current ).toEqual( {
			account,
			hasFinishedResolution: true,
		} );
		expect( hasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleBusinessProfileAccount',
			[]
		);
	} );
} );

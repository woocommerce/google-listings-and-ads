/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useSearchConsoleAccount from './useSearchConsoleAccount';

const mockGetSearchConsoleAccount = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

describe( 'useSearchConsoleAccount', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getSearchConsoleAccount: mockGetSearchConsoleAccount,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'returns the account data and resolution state from the store', () => {
		const account = { status: 'connected' };
		mockGetSearchConsoleAccount.mockReturnValue( account );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useSearchConsoleAccount() );

		expect( result.current ).toEqual( {
			searchConsoleAccount: account,
			hasFinishedResolution: true,
		} );
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'reports unfinished resolution while the account data is loading', () => {
		mockGetSearchConsoleAccount.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () => useSearchConsoleAccount() );

		expect( result.current ).toEqual( {
			searchConsoleAccount: null,
			hasFinishedResolution: false,
		} );
	} );
} );

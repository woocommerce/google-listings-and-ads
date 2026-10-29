/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import useGoogleSearchConsoleAccount from './useGoogleSearchConsoleAccount';
import { glaData } from '~/constants';

const mockGetGoogleSearchConsoleAccount = jest.fn();
const mockHasFinishedResolution = jest.fn();

jest.mock( '@wordpress/data', () => ( {
	__esModule: true,
	useSelect: jest.fn(),
} ) );

describe( 'useGoogleSearchConsoleAccount', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		useSelect.mockImplementation( ( cb ) =>
			cb( () => ( {
				getGoogleSearchConsoleAccount:
					mockGetGoogleSearchConsoleAccount,
				hasFinishedResolution: mockHasFinishedResolution,
			} ) )
		);
	} );

	it( 'returns the account data and resolution state from the store', () => {
		const account = { status: 'connected' };
		mockGetGoogleSearchConsoleAccount.mockReturnValue( account );
		mockHasFinishedResolution.mockReturnValue( true );

		const { result } = renderHook( () => useGoogleSearchConsoleAccount() );

		expect( result.current ).toEqual( {
			account,
			isConnected: true,
			isReportAvailable: true,
			hasFinishedResolution: true,
		} );
		expect( mockHasFinishedResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 'reports unfinished resolution while the account data is loading', () => {
		mockGetGoogleSearchConsoleAccount.mockReturnValue( null );
		mockHasFinishedResolution.mockReturnValue( false );

		const { result } = renderHook( () => useGoogleSearchConsoleAccount() );

		expect( result.current ).toEqual( {
			account: null,
			isConnected: false,
			isReportAvailable: false,
			hasFinishedResolution: false,
		} );
	} );

	describe( 'with a transient error checking the status', () => {
		beforeEach( () => {
			mockGetGoogleSearchConsoleAccount.mockReturnValue( {
				status: 'transient-error',
			} );
			mockHasFinishedResolution.mockReturnValue( true );
		} );

		afterEach( () => {
			glaData.searchConsoleConnected = false;
		} );

		it( 'makes the report available when the stored connection is connected', () => {
			glaData.searchConsoleConnected = true;

			const { result } = renderHook( () =>
				useGoogleSearchConsoleAccount()
			);

			expect( result.current.isConnected ).toBe( false );
			expect( result.current.isReportAvailable ).toBe( true );
		} );

		it( 'keeps the report unavailable when the stored connection is not connected', () => {
			glaData.searchConsoleConnected = false;

			const { result } = renderHook( () =>
				useGoogleSearchConsoleAccount()
			);

			expect( result.current.isReportAvailable ).toBe( false );
		} );
	} );
} );

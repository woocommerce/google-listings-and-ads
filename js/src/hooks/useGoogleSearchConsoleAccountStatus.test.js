/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleSearchConsoleAccountStatus from './useGoogleSearchConsoleAccountStatus';
import useSearchConsoleAccount from './useSearchConsoleAccount';

jest.mock( './useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);

describe( 'useGoogleSearchConsoleAccountStatus', () => {
	it( 'reports connected when the account status is connected', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: { status: 'connected' },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleAccountStatus()
		);

		expect( result.current ).toEqual( {
			status: 'connected',
			isConnected: true,
			isIncomplete: false,
			hasFinishedResolution: true,
		} );
	} );

	it( 'reports incomplete when the account status is incomplete', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: { status: 'incomplete' },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleAccountStatus()
		);

		expect( result.current ).toEqual( {
			status: 'incomplete',
			isConnected: false,
			isIncomplete: true,
			hasFinishedResolution: true,
		} );
	} );

	it( 'reports neither connected nor incomplete before the account resolves', () => {
		useSearchConsoleAccount.mockReturnValue( {
			searchConsoleAccount: null,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useGoogleSearchConsoleAccountStatus()
		);

		expect( result.current ).toEqual( {
			status: undefined,
			isConnected: false,
			isIncomplete: false,
			hasFinishedResolution: false,
		} );
	} );
} );

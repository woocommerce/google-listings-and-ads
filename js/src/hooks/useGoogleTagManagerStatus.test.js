/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleTagManagerStatus from './useGoogleTagManagerStatus';
import useGoogleTagManagerAccount from './useGoogleTagManagerAccount';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';

jest.mock( './useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);

describe( 'useGoogleTagManagerStatus', () => {
	it( 'is connected when the account status is connected', () => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: { status: GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.CONNECTED },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleTagManagerStatus() );

		expect( result.current ).toEqual( {
			isConnected: true,
			hasFinishedResolution: true,
		} );
	} );

	it.each( [
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.DISCONNECTED,
		GOOGLE_TAG_MANAGER_ACCOUNT_STATUS.INCOMPLETE,
	] )( 'is not connected when the account status is %s', ( status ) => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: { status },
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () => useGoogleTagManagerStatus() );

		expect( result.current.isConnected ).toBe( false );
	} );

	it( 'is not connected while the account is loading', () => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: null,
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () => useGoogleTagManagerStatus() );

		expect( result.current ).toEqual( {
			isConnected: false,
			hasFinishedResolution: false,
		} );
	} );
} );

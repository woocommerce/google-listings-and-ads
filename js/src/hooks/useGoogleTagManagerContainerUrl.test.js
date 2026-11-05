/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useGoogleTagManagerContainerUrl from './useGoogleTagManagerContainerUrl';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const connectedAccount = {
	status: 'connected',
	id: '6002847391',
	containerId: '98765432',
};

describe( 'useGoogleTagManagerContainerUrl', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

	it( 'returns the URL of the connected container', () => {
		const { result } = renderHook( () =>
			useGoogleTagManagerContainerUrl( connectedAccount )
		);

		expect( result.current ).toBe(
			'https://tagmanager.google.com/#/container/accounts/6002847391/containers/98765432'
		);
	} );

	it( 'resolves the URL to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		const { result } = renderHook( () =>
			useGoogleTagManagerContainerUrl( connectedAccount )
		);

		expect( result.current ).toBe(
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Fcontainer%2Faccounts%2F6002847391%2Fcontainers%2F98765432&Email=merchant%40example.com'
		);
	} );

	it.each( [
		[ 'the connection has not loaded', null ],
		[ 'no container is connected', { status: 'disconnected' } ],
		[
			'only an account is selected',
			{ status: 'incomplete', id: '6002847391' },
		],
		[
			'the connected container has no ID',
			{ status: 'connected', id: '6002847391', containerId: null },
		],
	] )( 'returns null when %s', ( _, account ) => {
		const { result } = renderHook( () =>
			useGoogleTagManagerContainerUrl( account )
		);

		expect( result.current ).toBeNull();
	} );
} );

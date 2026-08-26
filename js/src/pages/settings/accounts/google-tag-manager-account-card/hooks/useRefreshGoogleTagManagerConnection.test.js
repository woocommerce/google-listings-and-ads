/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useRefreshGoogleTagManagerConnection from './useRefreshGoogleTagManagerConnection';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/data', () => ( {
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );

describe( 'useRefreshGoogleTagManagerConnection', () => {
	let invalidateResolution;

	beforeEach( () => {
		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );
		useGoogleTagManagerAccount.mockReturnValue( { isResolving: false } );
	} );

	it( 're-invalidates the connection resolution when refresh is called', () => {
		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		result.current.refresh();

		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleTagManagerAccount',
			[]
		);
	} );

	it( 'exposes isResolving from the connection hook', () => {
		useGoogleTagManagerAccount.mockReturnValue( { isResolving: true } );

		const { result } = renderHook( () =>
			useRefreshGoogleTagManagerConnection()
		);

		expect( result.current.isResolving ).toBe( true );
	} );
} );

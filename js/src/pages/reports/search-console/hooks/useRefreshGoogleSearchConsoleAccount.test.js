/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useRefreshGoogleSearchConsoleAccount from './useRefreshGoogleSearchConsoleAccount';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import { useAppDispatch } from '~/data';

jest.mock( '~/data', () => ( {
	__esModule: true,
	useAppDispatch: jest.fn(),
} ) );

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () => ( {
	__esModule: true,
	default: jest.fn(),
} ) );

describe( 'useRefreshGoogleSearchConsoleAccount', () => {
	const invalidateResolution = jest.fn();
	const fetchGoogleSearchConsoleAccount = jest.fn();

	const setVisibility = ( state ) => {
		Object.defineProperty( document, 'visibilityState', {
			configurable: true,
			get: () => state,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
	};

	beforeEach( () => {
		jest.clearAllMocks();
		useAppDispatch.mockReturnValue( {
			invalidateResolution,
			fetchGoogleSearchConsoleAccount,
		} );
	} );

	it( 'should discard a status held from earlier, then stop waiting', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: true,
		} );

		const { result } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		expect( invalidateResolution ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
		expect( result.current.isRefreshPending ).toBe( false );
	} );

	it( 'should not wait when no status is held yet, since the first fetch is already fresh', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: false,
		} );

		const { result } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		expect( invalidateResolution ).not.toHaveBeenCalled();
		expect( result.current.isRefreshPending ).toBe( false );
	} );

	it( 'should not discard the status again when the fetch it started finishes', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: false,
		} );

		const { rerender } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: true,
		} );
		rerender();

		expect( invalidateResolution ).not.toHaveBeenCalled();
	} );

	it( 'should fetch the status again when the browser tab becomes visible, without discarding the stored one', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: false,
		} );
		renderHook( () => useRefreshGoogleSearchConsoleAccount() );

		setVisibility( 'hidden' );
		expect( fetchGoogleSearchConsoleAccount ).not.toHaveBeenCalled();

		setVisibility( 'visible' );
		expect( fetchGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).not.toHaveBeenCalled();
	} );

	it( 'should stop listening once the report is closed', () => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: false,
		} );
		const { unmount } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		unmount();
		setVisibility( 'visible' );

		expect( fetchGoogleSearchConsoleAccount ).not.toHaveBeenCalled();
	} );
} );

/**
 * External dependencies
 */
import { renderHook, waitFor } from '@testing-library/react';

/**
 * Internal dependencies
 */
import useRefreshGoogleSearchConsoleAccount, {
	MIN_RECHECK_INTERVAL,
} from './useRefreshGoogleSearchConsoleAccount';
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
	const refreshGoogleSearchConsoleAccount = jest.fn();
	let now;

	const mockResolution = ( hasFinishedResolution ) => {
		useGoogleSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution,
		} );
	};

	const showBrowserTab = ( state ) => {
		Object.defineProperty( document, 'visibilityState', {
			configurable: true,
			get: () => state,
		} );
		document.dispatchEvent( new Event( 'visibilitychange' ) );
	};

	beforeEach( () => {
		jest.clearAllMocks();
		now = 1000000;
		jest.spyOn( Date, 'now' ).mockImplementation( () => now );
		refreshGoogleSearchConsoleAccount.mockResolvedValue( undefined );
		useAppDispatch.mockReturnValue( { refreshGoogleSearchConsoleAccount } );
	} );

	afterEach( () => {
		Date.now.mockRestore();
		delete document.visibilityState;
	} );

	it( 'should check a status held from earlier and wait until the check settles', async () => {
		let settleCheck;
		refreshGoogleSearchConsoleAccount.mockReturnValue(
			new Promise( ( resolve ) => {
				settleCheck = resolve;
			} )
		);
		mockResolution( true );

		const { result } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		expect( refreshGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
		expect( result.current.isRefreshPending ).toBe( true );

		settleCheck();

		await waitFor( () =>
			expect( result.current.isRefreshPending ).toBe( false )
		);
		expect( refreshGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should not wait when no status is held yet, since the first fetch is already fresh', () => {
		mockResolution( false );

		const { result } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		expect( refreshGoogleSearchConsoleAccount ).not.toHaveBeenCalled();
		expect( result.current.isRefreshPending ).toBe( false );
	} );

	it( 'should not check again when the fetch it started finishes', () => {
		mockResolution( false );

		const { rerender } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		mockResolution( true );
		rerender();

		expect( refreshGoogleSearchConsoleAccount ).not.toHaveBeenCalled();
	} );

	it( 'should fetch the status again when the browser tab is shown after the minimum interval', () => {
		mockResolution( false );
		renderHook( () => useRefreshGoogleSearchConsoleAccount() );

		now += MIN_RECHECK_INTERVAL;
		showBrowserTab( 'hidden' );
		expect( refreshGoogleSearchConsoleAccount ).not.toHaveBeenCalled();

		showBrowserTab( 'visible' );
		expect( refreshGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should skip a re-check requested before the minimum interval has passed', () => {
		mockResolution( false );
		renderHook( () => useRefreshGoogleSearchConsoleAccount() );

		now += MIN_RECHECK_INTERVAL - 1;
		showBrowserTab( 'visible' );
		expect( refreshGoogleSearchConsoleAccount ).not.toHaveBeenCalled();

		now += 1;
		showBrowserTab( 'visible' );
		expect( refreshGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );

		now += 1;
		showBrowserTab( 'visible' );
		expect( refreshGoogleSearchConsoleAccount ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'should stop listening once the report is closed', () => {
		mockResolution( false );
		const { unmount } = renderHook( () =>
			useRefreshGoogleSearchConsoleAccount()
		);

		unmount();
		now += MIN_RECHECK_INTERVAL;
		showBrowserTab( 'visible' );

		expect( refreshGoogleSearchConsoleAccount ).not.toHaveBeenCalled();
	} );
} );

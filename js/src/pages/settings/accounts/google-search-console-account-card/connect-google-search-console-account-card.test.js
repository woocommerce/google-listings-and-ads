/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen, waitFor } from '@testing-library/react';
import { getQuery, getHistory, getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectGoogleSearchConsoleAccountCard from './connect-google-search-console-account-card';
import useGoogleSearchConsoleConnectRedirect from './hooks/useGoogleSearchConsoleConnectRedirect';
import useSearchConsoleSetupCompleteCallback from './hooks/useSearchConsoleSetupCompleteCallback';

jest.mock( './hooks/useGoogleSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleConnectRedirect' )
);
jest.mock( './hooks/useSearchConsoleSetupCompleteCallback', () =>
	jest.fn().mockName( 'useSearchConsoleSetupCompleteCallback' )
);
jest.mock( '@woocommerce/navigation', () => ( {
	getQuery: jest.fn().mockName( 'getQuery' ),
	getNewPath: jest.fn().mockName( 'getNewPath' ),
	getHistory: jest.fn().mockName( 'getHistory' ),
} ) );

describe( 'ConnectGoogleSearchConsoleAccountCard', () => {
	let handleConnectClick;
	let handleCompleteSetup;
	let historyReplace;

	beforeEach( () => {
		jest.clearAllMocks();

		getQuery.mockReturnValue( {} );
		getNewPath.mockReturnValue( '/new-path' );
		historyReplace = jest.fn().mockName( 'getHistory().replace' );
		getHistory.mockReturnValue( { replace: historyReplace } );

		handleConnectClick = jest.fn().mockName( 'handleConnectClick' );
		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect: handleConnectClick,
			loading: false,
		} );

		handleCompleteSetup = jest
			.fn()
			.mockName( 'handleCompleteSetup' )
			.mockResolvedValue( undefined );
		useSearchConsoleSetupCompleteCallback.mockReturnValue( [
			handleCompleteSetup,
		] );
	} );

	it( 'renders the Connect button on a plain page load', () => {
		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	it( 'does not complete setup on a plain page load', () => {
		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).not.toHaveBeenCalled();
	} );

	it( 'renders a "Connecting…" indicator instead of the Connect button when returning from a confirmed OAuth redirect', () => {
		getQuery.mockReturnValue( {
			'google-mc': 'connected',
			'google-service': 'search-console',
		} );

		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Connecting…' ) ).toBeInTheDocument();
	} );

	it( 'completes setup and cleans up the URL when returning from a confirmed OAuth redirect', async () => {
		getQuery.mockReturnValue( {
			'google-mc': 'connected',
			'google-service': 'search-console',
		} );

		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).toHaveBeenCalledTimes( 1 );
		await waitFor( () => {
			expect( historyReplace ).toHaveBeenCalledWith( '/new-path' );
		} );
		expect( getNewPath ).toHaveBeenCalledWith( {
			'google-mc': undefined,
			'google-service': undefined,
		} );
	} );

	it( 'does not complete setup when `google-mc=connected` return belongs to another service riding the same shared Google connection', () => {
		getQuery.mockReturnValue( {
			'google-mc': 'connected',
			'google-service': 'youtube',
		} );

		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	it( 'does not complete setup when `google-service=search-console` is present without a confirmed `google-mc` return', () => {
		getQuery.mockReturnValue( { 'google-service': 'search-console' } );

		render( <ConnectGoogleSearchConsoleAccountCard /> );

		expect( handleCompleteSetup ).not.toHaveBeenCalled();
		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );
} );

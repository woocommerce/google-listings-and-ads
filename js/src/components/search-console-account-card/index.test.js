/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SearchConsoleAccountCard from './';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';

jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);

jest.mock( './connect-search-console', () =>
	jest
		.fn()
		.mockName( 'ConnectSearchConsole' )
		.mockImplementation( () => <div>--Test--ConnectSearchConsole</div> )
);

jest.mock( './property-selector', () =>
	jest
		.fn()
		.mockName( 'PropertySelector' )
		.mockImplementation( () => <div>--Test--PropertySelector</div> )
);

jest.mock( './verification-step', () =>
	jest
		.fn()
		.mockName( 'VerificationStep' )
		.mockImplementation( () => <div>--Test--VerificationStep</div> )
);

jest.mock( './action-needed-card', () =>
	jest
		.fn()
		.mockName( 'ActionNeededCard' )
		.mockImplementation( () => <div>--Test--ActionNeededCard</div> )
);

jest.mock( './reconnect-card', () =>
	jest
		.fn()
		.mockName( 'ReconnectCard' )
		.mockImplementation( () => <div>--Test--ReconnectCard</div> )
);

jest.mock( './connection-failed-card', () =>
	jest
		.fn()
		.mockName( 'ConnectionFailedCard' )
		.mockImplementation( () => <div>--Test--ConnectionFailedCard</div> )
);

jest.mock( './incomplete-resume-card', () =>
	jest
		.fn()
		.mockName( 'IncompleteResumeCard' )
		.mockImplementation( () => <div>--Test--IncompleteResumeCard</div> )
);

jest.mock( './connected-search-console-account-card', () =>
	jest
		.fn()
		.mockName( 'ConnectedSearchConsoleAccountCard' )
		.mockImplementation( () => (
			<div>--Test--ConnectedSearchConsoleAccountCard</div>
		) )
);

describe( 'SearchConsoleAccountCard', () => {
	it( 'renders a spinner while the account connection data is loading', () => {
		useSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: false,
		} );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'status', { name: /Loading/ } )
		).toBeInTheDocument();
	} );

	it( 'renders the connect card when not connected', () => {
		useSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			searchConsoleAccount: { status: 'disconnected' },
		} );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByText( '--Test--ConnectSearchConsole' )
		).toBeInTheDocument();
	} );

	it.each( [
		[ 'property_selection', '--Test--PropertySelector' ],
		[ 'verification', '--Test--VerificationStep' ],
		[ 'action_needed', '--Test--ActionNeededCard' ],
		[ 'reconnect', '--Test--ReconnectCard' ],
		[ 'connection_failed', '--Test--ConnectionFailedCard' ],
		[ 'something_unrecognized', '--Test--IncompleteResumeCard' ],
	] )(
		'renders the correct sub-state card for the "%s" incomplete step',
		( step, expectedText ) => {
			useSearchConsoleAccount.mockReturnValue( {
				hasFinishedResolution: true,
				searchConsoleAccount: { status: 'incomplete', step },
			} );

			render( <SearchConsoleAccountCard /> );

			expect( screen.getByText( expectedText ) ).toBeInTheDocument();
		}
	);

	it( 'renders the connected card once the connection is complete', () => {
		const searchConsoleAccount = {
			status: 'connected',
			property: { url: 'https://example.com/' },
		};
		useSearchConsoleAccount.mockReturnValue( {
			hasFinishedResolution: true,
			searchConsoleAccount,
		} );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByText( '--Test--ConnectedSearchConsoleAccountCard' )
		).toBeInTheDocument();
	} );
} );

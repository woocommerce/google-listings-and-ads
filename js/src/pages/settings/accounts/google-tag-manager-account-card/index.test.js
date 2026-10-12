/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleTagManagerAccountCard from './index';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import FocusableAccountCard from '~/components/focusable-account-card';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/components/focusable-account-card', () =>
	jest.fn( ( { children } ) => children ).mockName( 'FocusableAccountCard' )
);
jest.mock( './allow-access-google-tag-manager-account-card', () =>
	jest
		.fn( () => <div>Allow access Google Tag Manager account card</div> )
		.mockName( 'AllowAccessGoogleTagManagerAccountCard' )
);
jest.mock( './connect-google-tag-manager-account-card', () =>
	jest
		.fn( () => <div>Connect Google Tag Manager account card</div> )
		.mockName( 'ConnectGoogleTagManagerAccountCard' )
);
jest.mock( './incomplete-google-tag-manager-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Tag Manager account card</div> )
		.mockName( 'IncompleteGoogleTagManagerAccountCard' )
);

const { CONNECTED, DISCONNECTED, INCOMPLETE } =
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

/**
 * Mocks `useGoogleAccount`.
 *
 * @param {boolean} [gtmRequired] Whether the `tagmanager.readonly` scope has been granted.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockGoogleAccount( gtmRequired = true, hasFinishedResolution = true ) {
	useGoogleAccount.mockReturnValue( {
		scope: { gtmRequired },
		hasFinishedResolution,
	} );
}

/**
 * Mocks `useGoogleTagManagerAccount`.
 *
 * @param {Object} account The account payload to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockAccount( account, hasFinishedResolution = true ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution,
	} );
}

describe( 'GoogleTagManagerAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockGoogleAccount();
	} );

	it( 'renders nothing until the Google account has resolved', () => {
		mockGoogleAccount( true, false );
		mockAccount( undefined, false );

		const { container } = render( <GoogleTagManagerAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the Allow access card when the tagmanager.readonly scope is missing, before checking the connection', () => {
		mockGoogleAccount( false );
		mockAccount( undefined, false );

		render( <GoogleTagManagerAccountCard /> );

		expect(
			screen.getByText( 'Allow access Google Tag Manager account card' )
		).toBeInTheDocument();
	} );

	it( 'renders nothing until the connection has resolved', () => {
		mockAccount( undefined, false );

		const { container } = render( <GoogleTagManagerAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'delegates the disconnected status to ConnectGoogleTagManagerAccountCard', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleTagManagerAccountCard /> );

		expect(
			screen.getByText( 'Connect Google Tag Manager account card' )
		).toBeInTheDocument();
	} );

	it( 'delegates the incomplete status to IncompleteGoogleTagManagerAccountCard', () => {
		mockAccount( { status: INCOMPLETE } );

		render( <GoogleTagManagerAccountCard /> );

		expect( IncompleteGoogleTagManagerAccountCard ).toHaveBeenCalled();
	} );

	it( 'renders the connected badge and account/container detail when connected', () => {
		mockAccount( {
			status: CONNECTED,
			id: '1',
			name: 'Enjoy Mommyhood',
			containerId: '111',
			containerName: 'woo',
			containerPublicId: 'GTM-AAA111',
		} );

		render( <GoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect( screen.getByText( 'woo (GTM-AAA111)' ) ).toBeInTheDocument();
	} );

	it( 'calls onDisconnect when the Disconnect menu item is clicked', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		mockAccount( {
			status: CONNECTED,
			id: '1',
			name: 'Enjoy Mommyhood',
			containerId: '111',
			containerName: 'woo',
			containerPublicId: 'GTM-AAA111',
		} );

		render( <GoogleTagManagerAccountCard onDisconnect={ onDisconnect } /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Tag Manager',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);

		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );

	it.each( [
		[ 'Allow access', () => mockGoogleAccount( false ) ],
		[ 'Connect', () => mockAccount( { status: DISCONNECTED } ) ],
		[ 'Incomplete', () => mockAccount( { status: INCOMPLETE } ) ],
		[
			'Connected',
			() =>
				mockAccount( { status: CONNECTED, id: '1', name: 'Account' } ),
		],
	] )(
		'wraps the %s card in a FocusableAccountCard keyed to Tag Manager',
		( _, setup ) => {
			setup();

			render( <GoogleTagManagerAccountCard /> );

			expect( FocusableAccountCard ).toHaveBeenCalledWith(
				expect.objectContaining( { id: 'tag-manager' } ),
				expect.anything()
			);
		}
	);

	it( 'does not render the FocusableAccountCard while the card data is resolving', () => {
		mockAccount( undefined, false );

		render( <GoogleTagManagerAccountCard /> );

		expect( FocusableAccountCard ).not.toHaveBeenCalled();
	} );
} );

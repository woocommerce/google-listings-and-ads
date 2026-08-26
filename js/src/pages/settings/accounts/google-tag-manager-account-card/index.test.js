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
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( './incomplete-google-tag-manager-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Tag Manager account card</div> )
		.mockName( 'IncompleteGoogleTagManagerAccountCard' )
);

const { CONNECTED, DISCONNECTED, NO_ACCOUNT } =
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

/**
 * Mocks `useGoogleTagManagerAccount`.
 *
 * @param {Object} account The account payload to mock.
 */
function mockAccount( account ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'GoogleTagManagerAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();
	} );

	it( 'delegates the disconnected status to IncompleteGoogleTagManagerAccountCard', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleTagManagerAccountCard /> );

		expect(
			screen.getByText( 'Incomplete Google Tag Manager account card' )
		).toBeInTheDocument();
	} );

	it( 'delegates any not-yet-connected status to IncompleteGoogleTagManagerAccountCard', () => {
		mockAccount( { status: NO_ACCOUNT } );

		render( <GoogleTagManagerAccountCard /> );

		expect( IncompleteGoogleTagManagerAccountCard ).toHaveBeenCalled();
	} );

	it( 'renders the connected badge and account/container detail when connected', () => {
		mockAccount( {
			status: CONNECTED,
			account: { accountId: '1', name: 'Enjoy Mommyhood' },
			container: {
				containerId: '111',
				publicId: 'GTM-AAA111',
				name: 'woo',
			},
		} );

		render( <GoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Enjoy Mommyhood ・ woo' )
		).toBeInTheDocument();
	} );

	it( 'calls onDisconnect when the Disconnect menu item is clicked', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		mockAccount( {
			status: CONNECTED,
			account: { accountId: '1', name: 'Enjoy Mommyhood' },
			container: {
				containerId: '111',
				publicId: 'GTM-AAA111',
				name: 'woo',
			},
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
} );

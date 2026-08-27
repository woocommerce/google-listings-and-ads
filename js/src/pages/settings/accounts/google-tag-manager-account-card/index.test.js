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
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useGoogleTagManagerContainers from './hooks/useGoogleTagManagerContainers';
import IncompleteGoogleTagManagerAccountCard from './incomplete-google-tag-manager-account-card';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/hooks/useExistingGoogleTagManagerAccounts', () =>
	jest.fn().mockName( 'useExistingGoogleTagManagerAccounts' )
);
jest.mock( './hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);
jest.mock( './incomplete-google-tag-manager-account-card', () =>
	jest
		.fn( () => <div>Incomplete Google Tag Manager account card</div> )
		.mockName( 'IncompleteGoogleTagManagerAccountCard' )
);

const { CONNECTED, DISCONNECTED, INCOMPLETE } =
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

		useExistingGoogleTagManagerAccounts.mockReturnValue( {
			existingAccounts: [ { id: '1', name: 'Enjoy Mommyhood' } ],
			hasFinishedResolution: true,
		} );
		useGoogleTagManagerContainers.mockReturnValue( {
			containers: [ { id: '111', publicId: 'GTM-AAA111', name: 'woo' } ],
			hasFinishedResolution: true,
		} );
	} );

	it( 'delegates the disconnected status to IncompleteGoogleTagManagerAccountCard', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <GoogleTagManagerAccountCard /> );

		expect(
			screen.getByText( 'Incomplete Google Tag Manager account card' )
		).toBeInTheDocument();
	} );

	it( 'delegates any not-yet-connected status to IncompleteGoogleTagManagerAccountCard', () => {
		mockAccount( { status: INCOMPLETE } );

		render( <GoogleTagManagerAccountCard /> );

		expect( IncompleteGoogleTagManagerAccountCard ).toHaveBeenCalled();
	} );

	it( 'renders the connected badge and account/container detail when connected', () => {
		mockAccount( { status: CONNECTED, id: '1', containerId: '111' } );

		render( <GoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect( screen.getByText( 'woo (GTM-AAA111)' ) ).toBeInTheDocument();
	} );

	it( 'calls onDisconnect when the Disconnect menu item is clicked', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		mockAccount( { status: CONNECTED, id: '1', containerId: '111' } );

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

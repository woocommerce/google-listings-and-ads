/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedGoogleTagManagerAccountCard from './connected-google-tag-manager-account-card';

const account = {
	status: 'connected',
	account: {
		accountId: '6002847391',
		name: 'Enjoy Mommyhood',
		tagManagerUrl:
			'https://tagmanager.google.com/#/admin/accounts/6002847391',
	},
	container: {
		containerId: '98765432',
		publicId: 'GTM-PR99HWXX',
		name: 'woo',
		tagManagerUrl:
			'https://tagmanager.google.com/#/container/accounts/6002847391/containers/98765432/workspaces',
	},
};

describe( 'ConnectedGoogleTagManagerAccountCard', () => {
	it( 'renders the connected account and container detail', () => {
		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect(
			screen.getByText( 'Enjoy Mommyhood ・ woo' )
		).toBeInTheDocument();
		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
	} );

	it( 'offers "Open Google Tag Manager" and "Disconnect" from the actions menu', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		render(
			<ConnectedGoogleTagManagerAccountCard
				account={ account }
				onDisconnect={ onDisconnect }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Tag Manager',
			} )
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: 'Open Google Tag Manager',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/admin/accounts/6002847391'
		);

		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);
		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );
} );

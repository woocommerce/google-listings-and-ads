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

// The connection record itself carries all the display data needed once connected.
const account = {
	status: 'connected',
	id: '6002847391',
	name: 'Enjoy Mommyhood',
	containerId: '98765432',
	containerName: 'woo',
	containerPublicId: 'GTM-PR99HWXX',
};

describe( 'ConnectedGoogleTagManagerAccountCard', () => {
	it( 'renders the connected account and container detail', () => {
		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/accounts/6002847391'
		);
		expect( screen.getByText( 'woo (GTM-PR99HWXX)' ) ).toBeInTheDocument();
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
			'https://tagmanager.google.com/#/accounts/6002847391'
		);

		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);
		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );
} );

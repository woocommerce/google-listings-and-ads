/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedGoogleTagManagerAccountCard from './index';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

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
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

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
		expect(
			screen.getByRole( 'link', {
				name: 'woo (GTM-PR99HWXX) (opens in a new tab)',
			} )
		).toBeInTheDocument();
		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
	} );

	it( 'links the container out to that container in Google Tag Manager', () => {
		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect( screen.getByText( 'Container' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'woo (GTM-PR99HWXX) (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/container/accounts/6002847391/containers/98765432'
		);
	} );

	it( 'no longer warns about duplicate Google Ads tracking', () => {
		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect(
			screen.queryByText( /already adds a Google Ads conversion tag/ )
		).not.toBeInTheDocument();
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

	it( 'resolves the outbound Google Tag Manager links to the connected Google account when its email is known', async () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
		const user = userEvent.setup();
		const accountAwareUrl =
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Faccounts%2F6002847391&Email=merchant%40example.com';

		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute( 'href', accountAwareUrl );
		expect(
			screen.getByRole( 'link', {
				name: 'woo (GTM-PR99HWXX) (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Fcontainer%2Faccounts%2F6002847391%2Fcontainers%2F98765432&Email=merchant%40example.com'
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
		).toHaveAttribute( 'href', accountAwareUrl );
	} );
} );

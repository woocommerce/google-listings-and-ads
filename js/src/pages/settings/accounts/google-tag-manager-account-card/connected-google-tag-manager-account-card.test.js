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
		expect( screen.getByText( 'woo (GTM-PR99HWXX)' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
	} );

	it( "warns that the plugin's Ads tracking may double-count with a GTM Ads tag", () => {
		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect(
			screen.getByText(
				( _, element ) =>
					element?.tagName === 'P' &&
					/already adds a Google Ads conversion tag/.test(
						element.textContent
					)
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: 'use this snippet (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://woocommerce.com/document/google-for-woocommerce/faq/#analytics-performance-tracking'
		);
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

	it( 'resolves both outbound Google Tag Manager links to the connected Google account when its email is known', async () => {
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

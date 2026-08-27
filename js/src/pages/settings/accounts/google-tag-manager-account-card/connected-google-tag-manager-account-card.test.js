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
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useGoogleTagManagerContainers from './hooks/useGoogleTagManagerContainers';

jest.mock( '~/hooks/useExistingGoogleTagManagerAccounts', () =>
	jest.fn().mockName( 'useExistingGoogleTagManagerAccounts' )
);
jest.mock( './hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);

// The connection record itself is flat — display data is looked up from the accounts/containers
// lists by matching `id`/`containerId`.
const account = {
	status: 'connected',
	id: '6002847391',
	containerId: '98765432',
};

describe( 'ConnectedGoogleTagManagerAccountCard', () => {
	beforeEach( () => {
		useExistingGoogleTagManagerAccounts.mockReturnValue( {
			existingAccounts: [
				{
					id: '6002847391',
					name: 'Enjoy Mommyhood',
					tagManagerUrl:
						'https://tagmanager.google.com/#/admin/accounts/6002847391',
				},
			],
			hasFinishedResolution: true,
		} );
		useGoogleTagManagerContainers.mockReturnValue( {
			containers: [
				{
					id: '98765432',
					publicId: 'GTM-PR99HWXX',
					name: 'woo',
					tagManagerUrl:
						'https://tagmanager.google.com/#/container/accounts/6002847391/containers/98765432/workspaces',
				},
			],
			hasFinishedResolution: true,
		} );
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
			'https://tagmanager.google.com/#/admin/accounts/6002847391'
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
			'https://tagmanager.google.com/#/admin/accounts/6002847391'
		);

		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);
		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'shows no detail/badge until the accounts and containers lists have resolved', () => {
		useGoogleTagManagerContainers.mockReturnValue( {
			containers: null,
			hasFinishedResolution: false,
		} );

		render( <ConnectedGoogleTagManagerAccountCard account={ account } /> );

		expect(
			screen.queryByText( 'Enjoy Mommyhood' )
		).not.toBeInTheDocument();
		expect( screen.queryByText( 'Connected' ) ).not.toBeInTheDocument();
	} );
} );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import IncompleteGoogleTagManagerAccountCard from './index';
import { GOOGLE_TAG_MANAGER_ACCOUNT_STATUS } from '~/constants';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useConnectGoogleTagManagerAccount from '../hooks/useConnectGoogleTagManagerAccount';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';
import useRefreshGoogleTagManagerConnection from '../hooks/useRefreshGoogleTagManagerConnection';

jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerContainer' )
);
jest.mock( '../hooks/useRefreshGoogleTagManagerConnection', () =>
	jest.fn().mockName( 'useRefreshGoogleTagManagerConnection' )
);

const { DISCONNECTED, NO_ACCOUNT, ACCOUNT_SELECTION, CONTAINER_SELECTION } =
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;

// `ExternalLink` appends this to the link's accessible name.
const CREATE_ACCOUNT_LINK_NAME = 'Create new account (opens in a new tab)';

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

describe( 'IncompleteGoogleTagManagerAccountCard', () => {
	let connect;
	let selectContainer;
	let refresh;

	beforeEach( () => {
		jest.clearAllMocks();

		connect = jest.fn().mockName( 'connect' );
		useConnectGoogleTagManagerAccount.mockReturnValue( {
			connect,
			loading: false,
		} );

		selectContainer = jest.fn().mockName( 'selectContainer' );
		useConnectGoogleTagManagerContainer.mockReturnValue( {
			selectContainer,
			loading: false,
		} );

		refresh = jest.fn().mockName( 'refresh' );
		useRefreshGoogleTagManagerConnection.mockReturnValue( {
			refresh,
			isResolving: false,
		} );
	} );

	it( 'renders the card shell but no indicator/detail content until the account has resolved', () => {
		useGoogleTagManagerAccount.mockReturnValue( {
			account: undefined,
			hasFinishedResolution: false,
		} );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Google Tag Manager' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', { name: 'Create new account' } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the zero-accounts CTA with an "Action needed" badge, no Connect button', async () => {
		const user = userEvent.setup();
		mockAccount( { status: NO_ACCOUNT, accounts: [] } );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'No Google Tag Manager account found' )
		).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/#/admin' );

		await user.click(
			screen.getByRole( 'button', { name: 'Check again' } )
		);
		expect( refresh ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'falls back to the zero-accounts CTA for the disconnected/error status', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).toBeInTheDocument();
	} );

	it( 'auto-selects and enables Connect immediately when exactly one account exists', async () => {
		const user = userEvent.setup();
		mockAccount( {
			status: ACCOUNT_SELECTION,
			accounts: [
				{
					accountId: '6002847391',
					name: 'Enjoy Mommyhood',
					tagManagerUrl:
						'https://tagmanager.google.com/#/admin/accounts/6002847391',
				},
			],
		} );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect(
			screen.getByText(
				'We found your existing Google Tag Manager account.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/admin/accounts/6002847391'
		);
		expect(
			screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/#/admin' );
		const connectButton = screen.getByRole( 'button', { name: 'Connect' } );
		expect( connectButton ).toBeEnabled();

		await user.click( connectButton );

		expect( connect ).toHaveBeenCalledWith( '6002847391' );
	} );

	it( 'shows a disabled Connect button until an account is picked when multiple exist', async () => {
		const user = userEvent.setup();
		mockAccount( {
			status: ACCOUNT_SELECTION,
			accounts: [
				{ accountId: '1', name: 'Account 1' },
				{ accountId: '2', name: 'Account 2' },
			],
		} );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect(
			screen.getByText( 'We found multiple Google Tag Manager accounts.' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).toHaveAttribute( 'href', 'https://tagmanager.google.com/#/admin' );
		const connectButton = screen.getByRole( 'button', { name: 'Connect' } );
		// Auto-selects the first option, so it's already enabled.
		expect( connectButton ).toBeEnabled();

		await user.selectOptions( screen.getByRole( 'combobox' ), '2' );
		await user.click( connectButton );

		expect( connect ).toHaveBeenCalledWith( '2' );
	} );

	it( 'shows the single container as plain text and saves it, with an "Action needed" badge', async () => {
		const user = userEvent.setup();
		mockAccount( {
			status: CONTAINER_SELECTION,
			account: {
				accountId: '6002847391',
				name: 'Enjoy Mommyhood',
				tagManagerUrl:
					'https://tagmanager.google.com/#/admin/accounts/6002847391',
			},
			containers: [
				{
					containerId: '98765432',
					publicId: 'GTM-PR99HWXX',
					name: 'woo',
				},
			],
		} );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect( screen.getByText( 'Container' ) ).toBeInTheDocument();
		expect( screen.getByText( 'woo (GTM-PR99HWXX)' ) ).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();

		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		expect( saveButton ).toBeEnabled();

		await user.click( saveButton );
		expect( selectContainer ).toHaveBeenCalledWith( '98765432' );

		expect(
			screen.getByRole( 'button', { name: 'Create new container' } )
		).toBeInTheDocument();
	} );

	it( 'shows a container select-control when multiple containers exist', async () => {
		const user = userEvent.setup();
		mockAccount( {
			status: CONTAINER_SELECTION,
			account: {
				accountId: '6002847391',
				name: 'Enjoy Mommyhood',
				tagManagerUrl:
					'https://tagmanager.google.com/#/admin/accounts/6002847391',
			},
			containers: [
				{
					containerId: '98765432',
					publicId: 'GTM-PR99HWXX',
					name: 'woo',
				},
				{
					containerId: '11223344',
					publicId: 'GTM-QQ11WWXX',
					name: 'blog',
				},
			],
		} );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		// Auto-selects the first option, so it's already enabled.
		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		expect( saveButton ).toBeEnabled();

		await user.selectOptions( screen.getByRole( 'combobox' ), '11223344' );
		await user.click( saveButton );

		expect( selectContainer ).toHaveBeenCalledWith( '11223344' );
	} );
} );

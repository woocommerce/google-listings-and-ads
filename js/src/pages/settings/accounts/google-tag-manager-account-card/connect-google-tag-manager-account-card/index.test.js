/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectGoogleTagManagerAccountCard from './index';
import { useAppDispatch } from '~/data';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useConnectGoogleTagManagerAccount from '../hooks/useConnectGoogleTagManagerAccount';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useExistingGoogleTagManagerAccounts', () =>
	jest.fn().mockName( 'useExistingGoogleTagManagerAccounts' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerAccount' )
);

// `ExternalLink` appends this to the link's accessible name.
const CREATE_ACCOUNT_LINK_NAME = 'Create new account (opens in a new tab)';

/**
 * Mocks `useExistingGoogleTagManagerAccounts` (the candidate accounts list).
 *
 * @param {Object[]} [existingAccounts] The accounts to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockExistingAccounts(
	existingAccounts,
	hasFinishedResolution = true
) {
	useExistingGoogleTagManagerAccounts.mockReturnValue( {
		existingAccounts,
		hasFinishedResolution,
	} );
}

describe( 'ConnectGoogleTagManagerAccountCard', () => {
	let connect;
	let fetchGoogleTagManagerAccount;
	let fetchExistingGoogleTagManagerAccounts;

	beforeEach( () => {
		jest.clearAllMocks();

		connect = jest.fn().mockName( 'connect' );
		useConnectGoogleTagManagerAccount.mockReturnValue( {
			connect,
			loading: false,
		} );

		fetchGoogleTagManagerAccount = jest
			.fn()
			.mockName( 'fetchGoogleTagManagerAccount' )
			.mockResolvedValue();
		fetchExistingGoogleTagManagerAccounts = jest
			.fn()
			.mockName( 'fetchExistingGoogleTagManagerAccounts' )
			.mockResolvedValue();
		useAppDispatch.mockReturnValue( {
			fetchGoogleTagManagerAccount,
			fetchExistingGoogleTagManagerAccounts,
		} );

		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	it( 'shows the zero-accounts CTA with an "Action needed" badge, no Connect button', async () => {
		const user = userEvent.setup();
		mockExistingAccounts( [] );

		render( <ConnectGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				"We couldn't find a Google Tag Manager account associated with your merchant@example.com account. If you have already created an account, click the 'Check again' button to fetch your account details."
			)
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
		expect( fetchGoogleTagManagerAccount ).toHaveBeenCalledTimes( 1 );
		expect( fetchExistingGoogleTagManagerAccounts ).toHaveBeenCalledTimes(
			1
		);
	} );

	it( 'auto-selects and enables Connect immediately when exactly one account exists', async () => {
		const user = userEvent.setup();
		mockExistingAccounts( [
			{ id: '6002847391', name: 'Enjoy Mommyhood' },
		] );

		render( <ConnectGoogleTagManagerAccountCard /> );

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
			'https://tagmanager.google.com/#/accounts/6002847391'
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
		mockExistingAccounts( [
			{ id: '1', name: 'Account 1' },
			{ id: '2', name: 'Account 2' },
		] );

		render( <ConnectGoogleTagManagerAccountCard /> );

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

	it( 'shows no indicator or detail content until the accounts list has resolved', () => {
		mockExistingAccounts( undefined, false );

		render( <ConnectGoogleTagManagerAccountCard /> );

		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByText(
				"We couldn't find a Google Tag Manager account"
			)
		).not.toBeInTheDocument();
	} );
} );

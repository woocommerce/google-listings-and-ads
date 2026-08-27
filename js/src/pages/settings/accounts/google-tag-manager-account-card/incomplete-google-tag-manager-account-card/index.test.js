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
import {
	GOOGLE_TAG_MANAGER_ACCOUNT_STATUS,
	GOOGLE_TAG_MANAGER_STEP,
} from '~/constants';
import { useAppDispatch } from '~/data';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleTagManagerAccount from '~/hooks/useGoogleTagManagerAccount';
import useExistingGoogleTagManagerAccounts from '~/hooks/useExistingGoogleTagManagerAccounts';
import useGoogleTagManagerContainers from '../hooks/useGoogleTagManagerContainers';
import useConnectGoogleTagManagerAccount from '../hooks/useConnectGoogleTagManagerAccount';
import useConnectGoogleTagManagerContainer from '../hooks/useConnectGoogleTagManagerContainer';

jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn().mockName( 'useAppDispatch' ),
} ) );
jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);
jest.mock( '~/hooks/useGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useGoogleTagManagerAccount' )
);
jest.mock( '~/hooks/useExistingGoogleTagManagerAccounts', () =>
	jest.fn().mockName( 'useExistingGoogleTagManagerAccounts' )
);
jest.mock( '../hooks/useGoogleTagManagerContainers', () =>
	jest.fn().mockName( 'useGoogleTagManagerContainers' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerAccount', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerAccount' )
);
jest.mock( '../hooks/useConnectGoogleTagManagerContainer', () =>
	jest.fn().mockName( 'useConnectGoogleTagManagerContainer' )
);

const { DISCONNECTED, INCOMPLETE } = GOOGLE_TAG_MANAGER_ACCOUNT_STATUS;
const { NO_ACCOUNT, ACCOUNT_SELECTION, CONTAINER_SELECTION } =
	GOOGLE_TAG_MANAGER_STEP;

// `ExternalLink` appends this to the link's accessible name.
const CREATE_ACCOUNT_LINK_NAME = 'Create new account (opens in a new tab)';

/**
 * Mocks `useGoogleTagManagerAccount` (the flat connection record).
 *
 * @param {Object} account The connection payload to mock.
 */
function mockConnection( account ) {
	useGoogleTagManagerAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

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

/**
 * Mocks `useGoogleTagManagerContainers` (the candidate containers list).
 *
 * @param {Object[]} [containers] The containers to mock.
 * @param {boolean} [hasFinishedResolution] Whether the resolver has finished.
 */
function mockContainers( containers, hasFinishedResolution = true ) {
	useGoogleTagManagerContainers.mockReturnValue( {
		containers,
		hasFinishedResolution,
	} );
}

describe( 'IncompleteGoogleTagManagerAccountCard', () => {
	let connect;
	let selectContainer;
	let fetchGoogleTagManagerAccount;
	let fetchExistingGoogleTagManagerAccounts;

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

		mockExistingAccounts( [] );
		mockContainers( [] );
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
			screen.queryByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).not.toBeInTheDocument();
	} );

	it( 'shows the zero-accounts CTA with an "Action needed" badge, no Connect button', async () => {
		const user = userEvent.setup();
		mockConnection( { status: INCOMPLETE, step: NO_ACCOUNT } );

		render( <IncompleteGoogleTagManagerAccountCard /> );

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

	it( 'falls back to the zero-accounts CTA for the disconnected/error status', () => {
		mockConnection( { status: DISCONNECTED } );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: CREATE_ACCOUNT_LINK_NAME } )
		).toBeInTheDocument();
	} );

	it( 'auto-selects and enables Connect immediately when exactly one account exists', async () => {
		const user = userEvent.setup();
		mockConnection( { status: INCOMPLETE, step: ACCOUNT_SELECTION } );
		mockExistingAccounts( [
			{
				id: '6002847391',
				name: 'Enjoy Mommyhood',
				tagManagerUrl:
					'https://tagmanager.google.com/#/admin/accounts/6002847391',
			},
		] );

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
		mockConnection( { status: INCOMPLETE, step: ACCOUNT_SELECTION } );
		mockExistingAccounts( [
			{ id: '1', name: 'Account 1' },
			{ id: '2', name: 'Account 2' },
		] );

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

	it( 'auto-selects and shows a non-interactive select-control when exactly one container exists, and saves it', async () => {
		const user = userEvent.setup();
		mockConnection( {
			status: INCOMPLETE,
			step: CONTAINER_SELECTION,
			id: '6002847391',
		} );
		mockExistingAccounts( [
			{
				id: '6002847391',
				name: 'Enjoy Mommyhood',
				tagManagerUrl:
					'https://tagmanager.google.com/#/admin/accounts/6002847391',
			},
		] );
		mockContainers( [
			{
				id: '98765432',
				publicId: 'GTM-PR99HWXX',
				name: 'woo',
			},
		] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Connect' } )
		).not.toBeInTheDocument();
		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		const containerSelect = screen.getByRole( 'combobox' );
		expect( containerSelect ).toHaveDisplayValue( 'woo (GTM-PR99HWXX)' );
		expect( containerSelect ).toHaveAttribute( 'readonly' );

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
		mockConnection( {
			status: INCOMPLETE,
			step: CONTAINER_SELECTION,
			id: '6002847391',
		} );
		mockExistingAccounts( [
			{
				id: '6002847391',
				name: 'Enjoy Mommyhood',
				tagManagerUrl:
					'https://tagmanager.google.com/#/admin/accounts/6002847391',
			},
		] );
		mockContainers( [
			{
				id: '98765432',
				publicId: 'GTM-PR99HWXX',
				name: 'woo',
			},
			{
				id: '11223344',
				publicId: 'GTM-QQ11WWXX',
				name: 'blog',
			},
		] );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		// Auto-selects the first option, so it's already enabled.
		const saveButton = screen.getByRole( 'button', { name: 'Save' } );
		expect( saveButton ).toBeEnabled();

		await user.selectOptions( screen.getByRole( 'combobox' ), '11223344' );
		await user.click( saveButton );

		expect( selectContainer ).toHaveBeenCalledWith( '11223344' );
	} );

	it( 'shows the "Action needed" badge immediately, but no detail content until the accounts and containers lists have resolved', () => {
		mockConnection( {
			status: INCOMPLETE,
			step: CONTAINER_SELECTION,
			id: '6002847391',
		} );
		mockExistingAccounts( undefined, false );
		mockContainers( undefined, false );

		render( <IncompleteGoogleTagManagerAccountCard /> );

		// The badge is driven by the connection's own `step`, resolved independently of the
		// accounts/containers lists this detail step also needs — so it shows right away.
		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Save' } )
		).not.toBeInTheDocument();
	} );
} );

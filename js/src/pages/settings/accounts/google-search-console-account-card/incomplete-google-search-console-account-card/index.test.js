/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import IncompleteGoogleSearchConsoleAccountCard from './index';
import { GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS } from '~/constants';
import useGoogleSearchConsoleAccount from '~/hooks/useGoogleSearchConsoleAccount';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useVerifyGoogleSearchConsoleProperty from '../hooks/useVerifyGoogleSearchConsoleProperty';
import useGoogleSearchConsoleConnectRedirect from '../hooks/useGoogleSearchConsoleConnectRedirect';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( '../hooks/useVerifyGoogleSearchConsoleProperty', () =>
	jest.fn().mockName( 'useVerifyGoogleSearchConsoleProperty' )
);
jest.mock( '../hooks/useGoogleSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleConnectRedirect' )
);
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

const {
	INCOMPLETE,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
	TRANSIENT_ERROR,
} = GOOGLE_SEARCH_CONSOLE_ACCOUNT_STATUS;

/**
 * Mocks `useGoogleSearchConsoleAccount`.
 *
 * @param {Object} account The account payload to mock.
 */
function mockAccount( account ) {
	useGoogleSearchConsoleAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'IncompleteGoogleSearchConsoleAccountCard', () => {
	let fetchSelectProperty;
	let createNotice;
	let invalidateResolution;
	let verifyClick;
	let connectClick;

	beforeEach( () => {
		jest.clearAllMocks();

		fetchSelectProperty = jest
			.fn()
			.mockName( 'fetchSelectProperty' )
			.mockResolvedValue( undefined );
		useApiFetchCallback.mockReturnValue( [
			fetchSelectProperty,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );

		verifyClick = jest.fn().mockName( 'handleVerifyClick' );
		useVerifyGoogleSearchConsoleProperty.mockReturnValue( {
			verify: verifyClick,
			loading: false,
		} );

		connectClick = jest.fn().mockName( 'handleConnectClick' );
		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect: connectClick,
			loading: false,
		} );
	} );

	it( 'renders a silent "setting up" treatment, not the selector, when there is no unresolved multi-match', () => {
		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect( screen.getByText( 'In progress' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Setting up Google Search Console' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View reports' } )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'renders the selector and selects a property when a genuine multi-match is unresolved', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: INCOMPLETE,
			matches: [
				{
					siteUrl: 'https://a.example.com/',
					permissionLevel: 'siteOwner',
					covers: true,
					usable: true,
				},
				{
					siteUrl: 'https://b.example.com/',
					permissionLevel: 'siteFullUser',
					covers: true,
					usable: true,
				},
			],
		} );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				'We found multiple Google Search Console properties'
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'In progress' ) ).toBeInTheDocument();
		expect(
			screen.queryByRole( 'option', { name: 'Create a new property' } )
		).not.toBeInTheDocument();

		const continueButton = screen.getByRole( 'button', {
			name: 'Continue',
		} );
		expect( continueButton ).toBeDisabled();

		await user.selectOptions(
			screen.getByRole( 'combobox' ),
			'https://a.example.com/'
		);
		expect( continueButton ).toBeEnabled();

		await user.click( continueButton );

		expect( fetchSelectProperty ).toHaveBeenCalledWith( {
			data: { site_url: 'https://a.example.com/' },
		} );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 'creates a new property via the explicit create action, not a dropdown option', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: INCOMPLETE,
			matches: [
				{
					siteUrl: 'https://a.example.com/',
					permissionLevel: 'siteOwner',
					covers: true,
					usable: true,
				},
				{
					siteUrl: 'https://b.example.com/',
					permissionLevel: 'siteFullUser',
					covers: true,
					usable: true,
				},
			],
		} );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		const createButton = screen.getByRole( 'button', {
			name: 'Or, create a new Google Search Console property',
		} );

		// Available without selecting anything from the dropdown first.
		expect( createButton ).toBeEnabled();

		await user.click( createButton );

		expect( fetchSelectProperty ).toHaveBeenCalledWith( { data: {} } );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 're-fetches and notifies when submitting a property choice fails', async () => {
		const user = userEvent.setup();

		fetchSelectProperty.mockRejectedValue( new Error( 'stale match' ) );

		mockAccount( {
			status: INCOMPLETE,
			matches: [
				{
					siteUrl: 'https://a.example.com/',
					permissionLevel: 'siteOwner',
					covers: true,
					usable: true,
				},
				{
					siteUrl: 'https://b.example.com/',
					permissionLevel: 'siteFullUser',
					covers: true,
					usable: true,
				},
			],
		} );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Or, create a new Google Search Console property',
			} )
		);

		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
		expect( createNotice ).toHaveBeenCalledWith(
			'error',
			expect.stringContaining( 'no longer available' )
		);
	} );

	it( 'renders the verify action for the action-needed status, with no request-access branch', async () => {
		const user = userEvent.setup();

		mockAccount( { status: ACTION_NEEDED } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Verify your site with Google' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /Learn more/ } )
		).toHaveAttribute(
			'href',
			'https://support.google.com/webmasters/answer/9008080'
		);
		expect(
			screen.queryByRole( 'link', { name: /Request access/ } )
		).not.toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( verifyClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders an error notice with a reconnect action when the connection expired', async () => {
		const user = userEvent.setup();

		mockAccount( { status: RECONNECT } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect( screen.getByText( 'Connection expired' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Your Google Search Console connection needs to be re-authorized.'
			)
		).toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Reconnect' } ) );
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders an error notice with a retry action when the connection attempt failed', async () => {
		const user = userEvent.setup();

		mockAccount( { status: CONNECTION_FAILED } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect( screen.getByText( 'Connection failed' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				"We couldn't connect your Google Search Console account. Please try again."
			)
		).toBeInTheDocument();

		await user.click( screen.getByRole( 'button', { name: 'Retry' } ) );
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a generic resume action for a transient status-check error', async () => {
		const user = userEvent.setup();

		mockAccount( { status: TRANSIENT_ERROR } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				"Your Google Search Console connection isn't complete yet."
			)
		).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		);
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );
} );

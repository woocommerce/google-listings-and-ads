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
import useGoogleSearchConsoleProperties from '~/hooks/useGoogleSearchConsoleProperties';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useGoogleSearchConsoleConnectRedirect from '../hooks/useGoogleSearchConsoleConnectRedirect';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useGoogleSearchConsoleAccount', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleAccount' )
);
jest.mock( '~/hooks/useGoogleSearchConsoleProperties', () =>
	jest.fn().mockName( 'useGoogleSearchConsoleProperties' )
);
jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
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

const PROPERTIES_PATH = '/wc/gla/search-console/properties';

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
	let setProperty;
	let createNotice;
	let invalidateResolution;
	let connectClick;

	/**
	 * Mocks `useGoogleSearchConsoleProperties`.
	 *
	 * @param {Array} properties The candidate properties payload to mock.
	 * @param {boolean} [hasFinishedResolution] Whether resolution has finished. Defaults to `true`.
	 */
	function mockProperties( properties, hasFinishedResolution = true ) {
		useGoogleSearchConsoleProperties.mockReturnValue( {
			properties,
			hasFinishedResolution,
		} );
	}

	beforeEach( () => {
		jest.clearAllMocks();

		mockProperties( [] );

		// The only `useApiFetchCallback` calls left in this render tree are POSTs (select/create
		// a property, or verify) — the properties list itself now comes from the store above.
		setProperty = jest
			.fn()
			.mockName( 'setProperty' )
			.mockResolvedValue( undefined );
		useApiFetchCallback.mockReturnValue( [
			setProperty,
			{ loading: false },
		] );

		createNotice = jest.fn().mockName( 'createNotice' );
		useDispatchCoreNotices.mockReturnValue( { createNotice } );

		invalidateResolution = jest.fn().mockName( 'invalidateResolution' );
		useAppDispatch.mockReturnValue( { invalidateResolution } );

		connectClick = jest.fn().mockName( 'handleConnectClick' );
		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect: connectClick,
			loading: false,
		} );
	} );

	it( 'renders a "Resume setup" button, not a badge or selector, when there is no unresolved multi-match', () => {
		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect( screen.queryByText( 'In progress' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByText( 'Setting up Google Search Console' )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'link', { name: 'View reports' } )
		).not.toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Continue' } )
		).not.toBeInTheDocument();

		expect(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		).toBeEnabled();
	} );

	it( 'shows the "Resume setup" button as loading while a connect request is in flight', () => {
		useGoogleSearchConsoleConnectRedirect.mockReturnValue( {
			connect: connectClick,
			loading: true,
		} );

		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		).toBeDisabled();
	} );

	it( 'shows a loading indicator while the properties list is still being fetched', () => {
		mockProperties( null, false );
		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText( 'Loading Google Search Console properties…' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'renders the selector and selects a property when a genuine multi-match is unresolved', async () => {
		const user = userEvent.setup();

		mockProperties( [
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
		] );
		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				'We found multiple Google Search Console properties'
			)
		).toBeInTheDocument();
		expect( screen.queryByText( 'Action needed' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'In progress' ) ).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'button', { name: 'Continue' } )
		).not.toBeInTheDocument();
		expect(
			screen.queryByRole( 'option', { name: 'Create a new property' } )
		).not.toBeInTheDocument();

		const saveButton = screen.getByRole( 'button', {
			name: 'Save',
		} );
		expect( saveButton ).toBeDisabled();

		await user.selectOptions(
			screen.getByRole( 'combobox' ),
			'https://a.example.com/'
		);
		expect( saveButton ).toBeEnabled();

		await user.click( saveButton );

		// `site_url` is bound into the `useApiFetchCallback` config itself, not passed at
		// call time — the fetch function is then invoked with no arguments.
		expect( useApiFetchCallback ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: PROPERTIES_PATH,
				method: 'POST',
				data: { site_url: 'https://a.example.com/' },
			} )
		);
		expect( setProperty ).toHaveBeenCalledWith();
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 'creates a new property via the explicit create action, not a dropdown option', async () => {
		const user = userEvent.setup();

		mockProperties( [
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
		] );
		mockAccount( { status: INCOMPLETE } );

		render( <IncompleteGoogleSearchConsoleAccountCard /> );

		const createButton = screen.getByRole( 'button', {
			name: 'Or, create a new Google Search Console property',
		} );

		// Available without selecting anything from the dropdown first.
		expect( createButton ).toBeEnabled();

		await user.click( createButton );

		expect( useApiFetchCallback ).toHaveBeenCalledWith(
			expect.objectContaining( {
				path: PROPERTIES_PATH,
				method: 'POST',
				data: {},
			} )
		);
		expect( setProperty ).toHaveBeenCalledWith();
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
	} );

	it( 're-fetches and notifies when submitting a property choice fails', async () => {
		const user = userEvent.setup();

		setProperty.mockRejectedValue( new Error( 'stale match' ) );
		mockProperties( [
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
		] );
		mockAccount( { status: INCOMPLETE } );

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
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleProperties',
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

		expect( useApiFetchCallback ).toHaveBeenCalledWith( {
			path: '/wc/gla/search-console/verify',
			method: 'POST',
		} );
		expect( setProperty ).toHaveBeenCalledTimes( 1 );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getGoogleSearchConsoleAccount',
			[]
		);
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

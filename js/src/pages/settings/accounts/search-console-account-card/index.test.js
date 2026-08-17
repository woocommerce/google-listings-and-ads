/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import SearchConsoleAccountCard from './index';
import {
	SEARCH_CONSOLE_ACCOUNT_STATUS,
	SEARCH_CONSOLE_ACCOUNT_STEP,
} from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useVerifySearchConsoleProperty from './hooks/useVerifySearchConsoleProperty';
import useSearchConsoleConnectRedirect from './hooks/useSearchConsoleConnectRedirect';
import { useAppDispatch } from '~/data';

jest.mock( '~/hooks/useSearchConsoleAccount', () =>
	jest.fn().mockName( 'useSearchConsoleAccount' )
);
jest.mock( '~/hooks/useApiFetchCallback', () =>
	jest.fn().mockName( 'useApiFetchCallback' )
);
jest.mock( '~/hooks/useDispatchCoreNotices', () =>
	jest.fn().mockName( 'useDispatchCoreNotices' )
);
jest.mock( './hooks/useVerifySearchConsoleProperty', () =>
	jest.fn().mockName( 'useVerifySearchConsoleProperty' )
);
jest.mock( './hooks/useSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useSearchConsoleConnectRedirect' )
);
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

const { CONNECTED, INCOMPLETE, DISCONNECTED } = SEARCH_CONSOLE_ACCOUNT_STATUS;
const {
	PROPERTY_SELECTION,
	VERIFICATION,
	ACTION_NEEDED,
	RECONNECT,
	CONNECTION_FAILED,
} = SEARCH_CONSOLE_ACCOUNT_STEP;

/**
 * Mocks `useSearchConsoleAccount`.
 *
 * @param {Object} account The account payload to mock.
 */
function mockAccount( account ) {
	useSearchConsoleAccount.mockReturnValue( {
		account,
		hasFinishedResolution: true,
	} );
}

describe( 'SearchConsoleAccountCard', () => {
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
		useVerifySearchConsoleProperty.mockReturnValue( {
			onClick: verifyClick,
			loading: false,
		} );

		connectClick = jest.fn().mockName( 'handleConnectClick' );
		useSearchConsoleConnectRedirect.mockReturnValue( {
			onClick: connectClick,
			loading: false,
		} );
	} );

	it( 'renders the Connect button when disconnected', () => {
		mockAccount( { status: DISCONNECTED } );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'button', { name: 'Connect' } )
		).toBeInTheDocument();
	} );

	it( 'renders the connected badge, property link, and reports menu action', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: CONNECTED,
			property: { url: 'https://example.com/' },
		} );

		render( <SearchConsoleAccountCard /> );

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /https:\/\/example\.com\// } )
		).toHaveAttribute( 'href', 'https://example.com/' );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Search Console',
			} )
		);

		expect(
			screen.getByRole( 'menuitem', {
				name: 'View Organic Search report',
			} )
		).toHaveAttribute(
			'href',
			'admin.php?page=wc-admin&path=%2Fgoogle%2Freports'
		);
	} );

	it( 'renders a loading state while a single/no-match property is being resolved', () => {
		mockAccount( {
			status: INCOMPLETE,
			step: PROPERTY_SELECTION,
			properties: [],
		} );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByText( 'Setting up Google Search Console' )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'We are connecting your account.' )
		).toBeInTheDocument();
		expect( screen.getByText( 'In progress' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View reports' } )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'renders the selector and selects a property when multiple candidates exist', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: INCOMPLETE,
			step: PROPERTY_SELECTION,
			properties: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
		} );

		render( <SearchConsoleAccountCard /> );

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
			data: { url: 'https://a.example.com/' },
		} );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'creates a new property via the explicit create action, not a dropdown option', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: INCOMPLETE,
			step: PROPERTY_SELECTION,
			properties: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
		} );

		render( <SearchConsoleAccountCard /> );

		const createButton = screen.getByRole( 'button', {
			name: 'Or, create a new Search Console property',
		} );

		// Available without selecting anything from the dropdown first.
		expect( createButton ).toBeEnabled();

		await user.click( createButton );

		expect( fetchSelectProperty ).toHaveBeenCalledWith( {
			data: { create_new: true },
		} );
		expect( invalidateResolution ).toHaveBeenCalledWith(
			'getSearchConsoleAccount',
			[]
		);
	} );

	it( 'renders the verify action for the verification step', async () => {
		const user = userEvent.setup();

		mockAccount( {
			status: INCOMPLETE,
			step: VERIFICATION,
			can_self_verify: true,
		} );

		render( <SearchConsoleAccountCard /> );

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

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);

		expect( verifyClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a request-access link when the merchant cannot self-verify', () => {
		mockAccount( {
			status: INCOMPLETE,
			step: VERIFICATION,
			can_self_verify: false,
			request_access_url: 'https://search.google.com/request-access',
		} );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByRole( 'link', { name: /Request access/ } )
		).toHaveAttribute( 'href', 'https://search.google.com/request-access' );
	} );

	it( 'renders a warning notice with a re-verify action for the action-needed step', async () => {
		const user = userEvent.setup();

		mockAccount( { status: INCOMPLETE, step: ACTION_NEEDED } );

		render( <SearchConsoleAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'Your Search Console property is no longer verified'
			)
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Verify it again to keep tracking organic performance.'
			)
		).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Verify site' } )
		);
		expect( verifyClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders an error notice with a reconnect action when the connection expired', async () => {
		const user = userEvent.setup();

		mockAccount( { status: INCOMPLETE, step: RECONNECT } );

		const { container } = render( <SearchConsoleAccountCard /> );

		expect(
			container.querySelector( '.components-notice__content' )
		).toHaveTextContent(
			'Connection expired: Your Search Console connection needs to be re-authorized.'
		);

		await user.click( screen.getByRole( 'button', { name: 'Reconnect' } ) );
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders an error notice with a retry action when the connection attempt failed', async () => {
		const user = userEvent.setup();

		mockAccount( { status: INCOMPLETE, step: CONNECTION_FAILED } );

		const { container } = render( <SearchConsoleAccountCard /> );

		expect(
			container.querySelector( '.components-notice__content' )
		).toHaveTextContent(
			"Connection failed: We couldn't connect your Search Console account. Please try again."
		);

		await user.click( screen.getByRole( 'button', { name: 'Retry' } ) );
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'renders a generic resume action for an unrecognized incomplete step', async () => {
		const user = userEvent.setup();

		mockAccount( { status: INCOMPLETE, step: 'something_unrecognized' } );

		render( <SearchConsoleAccountCard /> );

		expect(
			screen.getByText(
				"Your Search Console connection isn't complete yet."
			)
		).toBeInTheDocument();

		await user.click(
			screen.getByRole( 'button', { name: 'Resume setup' } )
		);
		expect( connectClick ).toHaveBeenCalledTimes( 1 );
	} );
} );

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import IncompleteSearchConsoleAccountCard from './index';
import { SEARCH_CONSOLE_ACCOUNT_STEP } from '~/constants';
import useSearchConsoleAccount from '~/hooks/useSearchConsoleAccount';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import useVerifySearchConsoleProperty from '../hooks/useVerifySearchConsoleProperty';
import useSearchConsoleConnectRedirect from '../hooks/useSearchConsoleConnectRedirect';
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
jest.mock( '../hooks/useVerifySearchConsoleProperty', () =>
	jest.fn().mockName( 'useVerifySearchConsoleProperty' )
);
jest.mock( '../hooks/useSearchConsoleConnectRedirect', () =>
	jest.fn().mockName( 'useSearchConsoleConnectRedirect' )
);
jest.mock( '~/data', () => ( {
	...jest.requireActual( '~/data' ),
	useAppDispatch: jest.fn(),
} ) );

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

describe( 'IncompleteSearchConsoleAccountCard', () => {
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

	it( 'renders a silent "setting up" treatment, not the selector, when no property matched yet', () => {
		mockAccount( { step: PROPERTY_SELECTION, properties: [] } );

		render( <IncompleteSearchConsoleAccountCard /> );

		expect( screen.getByText( 'In progress' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Setting up Google Search Console' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View reports' } )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'renders a silent "setting up" treatment, not the selector, when a single property matched', () => {
		mockAccount( {
			step: PROPERTY_SELECTION,
			properties: [ { url: 'https://a.example.com/', selectable: true } ],
		} );

		render( <IncompleteSearchConsoleAccountCard /> );

		expect( screen.getByText( 'In progress' ) ).toBeInTheDocument();
		expect(
			screen.getByText( 'Setting up Google Search Console' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'combobox' ) ).not.toBeInTheDocument();
	} );

	it( 'renders the selector and selects a property when multiple candidates exist', async () => {
		const user = userEvent.setup();

		mockAccount( {
			step: PROPERTY_SELECTION,
			properties: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
		} );

		render( <IncompleteSearchConsoleAccountCard /> );

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
			step: PROPERTY_SELECTION,
			properties: [
				{ url: 'https://a.example.com/', selectable: true },
				{ url: 'https://b.example.com/', selectable: true },
			],
		} );

		render( <IncompleteSearchConsoleAccountCard /> );

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

		mockAccount( { step: VERIFICATION, can_self_verify: true } );

		render( <IncompleteSearchConsoleAccountCard /> );

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
			step: VERIFICATION,
			can_self_verify: false,
			request_access_url: 'https://search.google.com/request-access',
		} );

		render( <IncompleteSearchConsoleAccountCard /> );

		expect( screen.getByText( 'Action needed' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: /Request access/ } )
		).toHaveAttribute( 'href', 'https://search.google.com/request-access' );
	} );

	it( 'renders a warning notice with a re-verify action for the action-needed step', async () => {
		const user = userEvent.setup();

		mockAccount( { step: ACTION_NEEDED } );

		render( <IncompleteSearchConsoleAccountCard /> );

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

		mockAccount( { step: RECONNECT } );

		const { container } = render( <IncompleteSearchConsoleAccountCard /> );

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

		mockAccount( { step: CONNECTION_FAILED } );

		const { container } = render( <IncompleteSearchConsoleAccountCard /> );

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

		mockAccount( { step: 'something_unrecognized' } );

		render( <IncompleteSearchConsoleAccountCard /> );

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

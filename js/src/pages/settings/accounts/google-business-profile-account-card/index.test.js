/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import GoogleBusinessProfileAccountCard from './index';
import useGoogleAccount from '~/hooks/useGoogleAccount';
import useGoogleBusinessProfileAccount from '~/hooks/useGoogleBusinessProfileAccount';

jest.mock( '~/hooks/useGoogleAccount' );
jest.mock( '~/hooks/useGoogleBusinessProfileAccount' );
jest.mock(
	'~/components/focusable-account-card',
	() =>
		function MockFocusableAccountCard( { id, children } ) {
			return <div data-focusable-id={ id }>{ children }</div>;
		}
);
jest.mock(
	'./allow-access-google-business-profile-account-card',
	() =>
		function MockAllowAccessGoogleBusinessProfileAccountCard() {
			return <div>Allow access card</div>;
		}
);
jest.mock(
	'./connect-google-business-profile-account-card',
	() =>
		function MockConnectGoogleBusinessProfileAccountCard() {
			return <div>Connect card</div>;
		}
);
jest.mock(
	'./connected-google-business-profile-account-card',
	() =>
		function MockConnectedGoogleBusinessProfileAccountCard( {
			account,
			onDisconnect,
		} ) {
			return (
				<button onClick={ onDisconnect }>
					Connected card for { account.id }
				</button>
			);
		}
);

const location = { id: '1111', title: "Jane's Bakery" };

describe( 'GoogleBusinessProfileAccountCard', () => {
	beforeEach( () => {
		jest.clearAllMocks();

		useGoogleAccount.mockReturnValue( {
			scope: { gbpRequired: true },
			hasFinishedResolution: true,
		} );
		useGoogleBusinessProfileAccount.mockReturnValue( {
			account: { status: 'disconnected' },
			hasFinishedResolution: true,
		} );
	} );

	const expectCardToBeFocusable = ( text ) => {
		expect(
			screen.getByText( text ).closest( '[data-focusable-id]' )
		).toHaveAttribute( 'data-focusable-id', 'business-profile' );
	};

	it( 'renders nothing while the Google Account is resolving', () => {
		useGoogleAccount.mockReturnValue( {
			scope: { gbpRequired: false },
			hasFinishedResolution: false,
		} );

		const { container } = render( <GoogleBusinessProfileAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the allow-access card when the scope is missing', () => {
		useGoogleAccount.mockReturnValue( {
			scope: { gbpRequired: false },
			hasFinishedResolution: true,
		} );

		render( <GoogleBusinessProfileAccountCard /> );

		expectCardToBeFocusable( 'Allow access card' );
	} );

	it( 'renders nothing while the connection is resolving', () => {
		useGoogleBusinessProfileAccount.mockReturnValue( {
			account: null,
			hasFinishedResolution: false,
		} );

		const { container } = render( <GoogleBusinessProfileAccountCard /> );

		expect( container ).toBeEmptyDOMElement();
	} );

	it( 'shows the connect card when no location is connected', () => {
		render( <GoogleBusinessProfileAccountCard /> );

		expectCardToBeFocusable( 'Connect card' );
	} );

	it( 'shows the connected card for the connected location and forwards disconnect', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );
		useGoogleBusinessProfileAccount.mockReturnValue( {
			account: { status: 'connected', ...location },
			hasFinishedResolution: true,
		} );

		render(
			<GoogleBusinessProfileAccountCard onDisconnect={ onDisconnect } />
		);

		expectCardToBeFocusable( 'Connected card for 1111' );

		await user.click(
			screen.getByRole( 'button', {
				name: 'Connected card for 1111',
			} )
		);

		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );
} );

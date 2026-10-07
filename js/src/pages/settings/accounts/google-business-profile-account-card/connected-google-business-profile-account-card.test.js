/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedGoogleBusinessProfileAccountCard from './connected-google-business-profile-account-card';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount' );

const location = {
	name: 'locations/1111',
	title: "Jane's Bakery",
	storefrontAddress: {
		addressLines: [ '2423 1st Ave' ],
		locality: 'Seattle',
		administrativeArea: 'WA',
		postalCode: '98121',
		regionCode: 'US',
	},
	metadata: { mapsUri: 'https://maps.google.com/?cid=1111' },
};

describe( 'ConnectedGoogleBusinessProfileAccountCard', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );
	} );

	it( 'shows the "Connected" badge and the connected address, linked to the listing under the connected Google Account', () => {
		render(
			<ConnectedGoogleBusinessProfileAccountCard
				location={ location }
				onDisconnect={ jest.fn() }
			/>
		);

		expect( screen.getByText( 'Connected' ) ).toBeInTheDocument();

		const link = screen.getByRole( 'link', {
			name: /2423 1st Ave, Seattle, WA 98121, US/,
		} );
		expect( link ).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fmaps.google.com%2F%3Fcid%3D1111&Email=merchant%40example.com'
		);
		expect( link ).toHaveAttribute( 'target', '_blank' );
	} );

	it( 'shows the address as plain text when the listing has no Maps URL', () => {
		render(
			<ConnectedGoogleBusinessProfileAccountCard
				location={ { ...location, metadata: {} } }
				onDisconnect={ jest.fn() }
			/>
		);

		expect(
			screen.getByText( '2423 1st Ave, Seattle, WA 98121, US' )
		).toBeInTheDocument();
		expect( screen.queryByRole( 'link' ) ).not.toBeInTheDocument();
	} );

	it( 'offers "View Google Business Profile" in the actions menu, linking to the listing under the connected Google Account', async () => {
		const user = userEvent.setup();

		render(
			<ConnectedGoogleBusinessProfileAccountCard
				location={ location }
				onDisconnect={ jest.fn() }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Business Profile',
			} )
		);

		const item = screen.getByRole( 'menuitem', {
			name: 'View Google Business Profile',
		} );
		expect( item ).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Fmaps.google.com%2F%3Fcid%3D1111&Email=merchant%40example.com'
		);
		expect( item ).toHaveAttribute( 'target', '_blank' );
	} );

	it( 'leaves "View Google Business Profile" out when the listing has no Maps URL', async () => {
		const user = userEvent.setup();

		render(
			<ConnectedGoogleBusinessProfileAccountCard
				location={ { ...location, metadata: {} } }
				onDisconnect={ jest.fn() }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Business Profile',
			} )
		);

		expect(
			screen.queryByRole( 'menuitem', {
				name: 'View Google Business Profile',
			} )
		).not.toBeInTheDocument();
		expect(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		).toBeInTheDocument();
	} );

	it( 'calls onDisconnect from the actions menu', async () => {
		const user = userEvent.setup();
		const onDisconnect = jest.fn().mockName( 'onDisconnect' );

		render(
			<ConnectedGoogleBusinessProfileAccountCard
				location={ location }
				onDisconnect={ onDisconnect }
			/>
		);

		await user.click(
			screen.getByRole( 'button', {
				name: 'Account actions for Google Business Profile',
			} )
		);
		await user.click(
			screen.getByRole( 'menuitem', { name: 'Disconnect' } )
		);

		expect( onDisconnect ).toHaveBeenCalledTimes( 1 );
	} );
} );

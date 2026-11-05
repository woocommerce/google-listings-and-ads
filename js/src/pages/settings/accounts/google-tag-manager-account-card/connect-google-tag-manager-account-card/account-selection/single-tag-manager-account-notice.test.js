/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import SingleTagManagerAccountNotice from './single-tag-manager-account-notice';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

const account = { id: '6002847391', name: 'Enjoy Mommyhood' };

describe( 'SingleTagManagerAccountNotice', () => {
	it( "renders the account's name and its ID linked out to Google Tag Manager", () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );

		render( <SingleTagManagerAccountNotice account={ account } /> );

		expect( screen.getByText( 'Enjoy Mommyhood' ) ).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/accounts/6002847391'
		);
	} );

	it( 'resolves the account link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render( <SingleTagManagerAccountNotice account={ account } /> );

		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://accounts.google.com/accountchooser?continue=https%3A%2F%2Ftagmanager.google.com%2F%23%2Faccounts%2F6002847391&Email=merchant%40example.com'
		);
	} );
} );

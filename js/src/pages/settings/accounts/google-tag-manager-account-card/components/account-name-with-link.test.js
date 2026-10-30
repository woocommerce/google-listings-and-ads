/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountNameWithLink from './account-name-with-link';
import useGoogleAccount from '~/hooks/useGoogleAccount';

jest.mock( '~/hooks/useGoogleAccount', () =>
	jest.fn().mockName( 'useGoogleAccount' )
);

describe( 'AccountNameWithLink', () => {
	beforeEach( () => {
		useGoogleAccount.mockReturnValue( { google: undefined } );
	} );

	it( "renders the account's name followed by its ID, linked out to that account in Google Tag Manager", () => {
		render(
			<AccountNameWithLink
				account={ { id: '6002847391', name: 'Enjoy Mommyhood' } }
			/>
		);

		expect(
			screen.getByText( 'Enjoy Mommyhood', { exact: false } )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', {
				name: '6002847391 (opens in a new tab)',
			} )
		).toHaveAttribute(
			'href',
			'https://tagmanager.google.com/#/accounts/6002847391'
		);
	} );

	it( 'resolves the link to the connected Google account when its email is known', () => {
		useGoogleAccount.mockReturnValue( {
			google: { email: 'merchant@example.com' },
		} );

		render(
			<AccountNameWithLink
				account={ { id: '6002847391', name: 'Enjoy Mommyhood' } }
			/>
		);

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

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountNameWithLink from './account-name-with-link';

describe( 'AccountNameWithLink', () => {
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
} );

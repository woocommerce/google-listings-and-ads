/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import AccountDetailRow from './account-detail-row';

describe( 'AccountDetailRow', () => {
	it( 'renders the label followed by its value', () => {
		render( <AccountDetailRow label="Container">woo</AccountDetailRow> );

		expect( screen.getByText( 'Container' ) ).toBeInTheDocument();
		expect( screen.getByText( 'woo' ) ).toBeInTheDocument();
	} );

	it( 'renders the indicator when one is given', () => {
		render(
			<AccountDetailRow
				label="Container"
				indicator={ <span>Active</span> }
			>
				woo
			</AccountDetailRow>
		);

		expect( screen.getByText( 'Active' ) ).toBeInTheDocument();
	} );

	it( 'renders no indicator wrapper without an indicator', () => {
		const { container } = render(
			<AccountDetailRow label="Container">woo</AccountDetailRow>
		);

		expect(
			container.querySelector( '.gla-account-detail-row__indicator' )
		).not.toBeInTheDocument();
	} );
} );

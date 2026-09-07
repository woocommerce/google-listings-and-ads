/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { getHistory } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import ConnectedSuccessNotice from './connected-success-notice';

jest.mock( '@woocommerce/navigation', () => ( {
	getHistory: jest.fn(),
} ) );

jest.mock( '~/utils/urls', () => ( {
	geReportsUrl: jest.fn( () => '/reports-url' ),
} ) );

describe( 'ConnectedSuccessNotice', () => {
	let push;

	beforeEach( () => {
		push = jest.fn().mockName( 'push' );
		getHistory.mockReturnValue( { push } );
	} );

	it( 'renders the success message and a button to reports', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'button', { name: 'View reports' } )
		).toBeInTheDocument();
	} );

	it( 'navigates to the reports page via the SPA router when clicked', async () => {
		const user = userEvent.setup();

		render( <ConnectedSuccessNotice /> );

		await user.click(
			screen.getByRole( 'button', { name: 'View reports' } )
		);

		expect( push ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'hides itself once dismissed', async () => {
		const user = userEvent.setup();

		render( <ConnectedSuccessNotice /> );

		await user.click( screen.getByRole( 'button', { name: 'Close' } ) );

		expect(
			screen.queryByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).not.toBeInTheDocument();
	} );
} );

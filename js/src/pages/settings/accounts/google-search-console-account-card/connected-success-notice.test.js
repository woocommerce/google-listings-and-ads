/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import ConnectedSuccessNotice from './connected-success-notice';

describe( 'ConnectedSuccessNotice', () => {
	it( 'renders the success message and a link to reports', () => {
		render( <ConnectedSuccessNotice /> );

		expect(
			screen.getByText(
				'We connected and verified a property for you. Your search data will start to appear over the next few days.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'View reports' } )
		).toBeInTheDocument();
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

/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import AddMarketModal from './';

describe( 'AddMarketModal', () => {
	test( 'renders the title and the placeholder body', () => {
		render( <AddMarketModal onRequestClose={ () => {} } /> );

		expect(
			screen.getByRole( 'dialog', { name: 'Add market' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Adding a new market.' )
		).toBeInTheDocument();
	} );

	test( 'invokes onRequestClose when the footer Close button is clicked', async () => {
		const user = userEvent.setup();
		const onRequestClose = jest.fn();
		render( <AddMarketModal onRequestClose={ onRequestClose } /> );

		// `getByRole('button', { name: 'Close' })` matches both the
		// `<Modal>`'s X button (aria-label) and the footer button. Use the
		// `is-tertiary` variant class to target only our footer button.
		const footerCloseButton = document.querySelector(
			'.app-modal__footer .is-tertiary'
		);
		await user.click( footerCloseButton );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );
} );

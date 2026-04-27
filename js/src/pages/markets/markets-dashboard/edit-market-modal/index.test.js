/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import EditMarketModal from './';

const market = { id: 'primary', label: 'Primary Market' };

describe( 'EditMarketModal', () => {
	test( 'renders the title and the market name being edited', () => {
		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit market' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Editing Primary Market.' )
		).toBeInTheDocument();
	} );

	test( 'invokes onRequestClose when the footer Close button is clicked', async () => {
		const user = userEvent.setup();
		const onRequestClose = jest.fn();
		render(
			<EditMarketModal
				market={ market }
				onRequestClose={ onRequestClose }
			/>
		);

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

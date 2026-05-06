/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import useSettings from '~/hooks/useSettings';
import EditMarketModal from './';

jest.mock( '~/hooks/useSettings' );

const market = { id: 'primary', label: 'Primary Market' };

describe( 'EditMarketModal', () => {
	beforeEach( () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
	} );

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

	test( 'renders the estimated shipping rates block', () => {
		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			screen.getByText( 'Estimated shipping rates' )
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'checkbox', {
				name: 'Free shipping over a specific order value',
			} )
		).toBeInTheDocument();
	} );

	test( 'renders the estimated shipping times block', () => {
		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			screen.getByText( 'Estimated shipping times' )
		).toBeInTheDocument();
		expect(
			screen.getByText(
				'Delivery times apply per country, regardless of language or currency.'
			)
		).toBeInTheDocument();
		expect( screen.getByText( 'to' ) ).toBeInTheDocument();
		expect( screen.getByDisplayValue( '3' ) ).toBeInTheDocument();
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

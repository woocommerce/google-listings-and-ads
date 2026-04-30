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
		global.glaData.isMultiLingualStore = false;
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
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

	test( 'renders a shipping notice when shipping_rate is manual and single lingual store', () => {
		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		const notice = document.querySelector(
			'.gla-edit-market-modal__notice'
		);
		expect( notice ).toBeInTheDocument();
		expect( notice ).toHaveTextContent(
			'Shipping is managed in Google Merchant Center. Configure shipping rates and times for each currency in your Merchant Center account.'
		);
	} );

	test( 'does not render the shipping notice on multi lingual stores', () => {
		global.glaData.isMultiLingualStore = true;

		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			document.querySelector( '.gla-edit-market-modal__notice' )
		).not.toBeInTheDocument();
	} );

	test( 'does not render the shipping notice when shipping_rate is not manual', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'flat' },
		} );

		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			document.querySelector( '.gla-edit-market-modal__notice' )
		).not.toBeInTheDocument();
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

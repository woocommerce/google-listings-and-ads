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
import useSettings from '~/hooks/useSettings';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/hooks/useSettings' );

describe( 'AddMarketModal', () => {
	beforeEach( () => {
		global.glaData.isMultiLingualStore = false;

		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.MANUAL },
		} );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
	} );

	test( 'renders the title and the placeholder body', () => {
		render( <AddMarketModal onRequestClose={ () => {} } /> );

		expect(
			screen.getByRole( 'dialog', { name: 'Add market' } )
		).toBeInTheDocument();
		expect(
			screen.getByText( 'Install a multilingual plugin to add markets' )
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

	test( 'should render the plugin list and title and button appears when conditions are met', () => {
		global.glaData.isMultiLingualStore = false;
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.MANUAL },
		} );

		try {
			render( <AddMarketModal onRequestClose={ () => {} } /> );

			expect( screen.getByText( 'WPML' ) ).toBeInTheDocument();
			expect(
				screen.getByText(
					'WooCommerce integration that handles multi-currency natively.'
				)
			).toBeInTheDocument();
			expect(
				screen.getByRole( 'link', { name: 'Learn more' } )
			).toBeInTheDocument();
		} finally {
			delete global.glaData.isMultiLingualStore;
			useSettings.mockReset();
		}
	} );
} );

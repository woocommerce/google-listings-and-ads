/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import { useAppDispatch } from '~/data';
import useSettings from '~/hooks/useSettings';
import EditMarketModal from './';

jest.mock( '~/data', () => ( { useAppDispatch: jest.fn() } ) );
jest.mock( '~/hooks/useSettings' );
jest.mock( './edit-primary-audience', () => () => null );

const market = { id: 'primary', label: 'Primary Market' };
const targetAudience = { countries: [ 'US' ] };

describe( 'EditMarketModal', () => {
	beforeEach( () => {
		useAppDispatch.mockReturnValue( {
			updateMarket: jest.fn(),
			invalidateResolution: jest.fn(),
		} );
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
	} );

	test( 'renders the title for the primary market', () => {
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit primary market' } )
		).toBeInTheDocument();
	} );

	test( 'invokes onRequestClose when the footer Cancel button is clicked', async () => {
		const user = userEvent.setup();
		const onRequestClose = jest.fn();
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ onRequestClose }
			/>
		);

		// `getByRole('button', { name: 'Cancel' })` matches both the
		// `<Modal>`'s X button (aria-label) and the footer button. Use the
		// `is-tertiary` variant class to target only our footer button.
		const footerCancelButton = document.querySelector(
			'.app-modal__footer .is-tertiary'
		);
		await user.click( footerCancelButton );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );
} );

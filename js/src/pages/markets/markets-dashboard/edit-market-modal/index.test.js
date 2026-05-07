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

	test( 'renders the estimated shipping rates block', () => {
		render(
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
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
			<EditMarketModal
				market={ market }
				targetAudience={ targetAudience }
				onRequestClose={ () => {} }
			/>
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

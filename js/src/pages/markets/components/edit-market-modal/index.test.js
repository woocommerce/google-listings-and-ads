/**
 * External dependencies
 */
import '@testing-library/jest-dom';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';

/**
 * Internal dependencies
 */
import MarketForm from '../market-form';
import EditMarketModal from '.';
import AppSpinner from '~/components/app-spinner';

// MarketForm pulls in useSettings → useSelect( STORE_KEY ) which requires the
// wc/gla store to be registered. Mock it to avoid that dependency.
jest.mock( '../market-form', () =>
	jest.fn( ( { children } ) => <>{ children }</> )
);

// MarketFields requires AdaptiveForm context. Mock it so it renders without
// that context since MarketForm itself is mocked above.
jest.mock( '../market-fields', () => jest.fn( () => null ) );

// EditMarketButtons uses useAdaptiveFormContext. Mock the hook so the buttons
// render without a real AdaptiveForm provider.
jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn(),
} ) );

const market = { id: 'primary', label: 'Primary Market' };

describe( 'EditMarketModal', () => {
	beforeEach( () => {
		const { useAdaptiveFormContext } = jest.requireMock(
			'~/components/adaptive-form'
		);
		useAdaptiveFormContext.mockReturnValue( {
			adapter: { isSaving: false, showValidation: jest.fn() },
			isValidForm: true,
			handleSubmit: jest.fn(),
		} );
		MarketForm.mockClear();
	} );

	test( 'renders the title for the primary market', () => {
		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit primary market' } )
		).toBeInTheDocument();
	} );

	test( 'renders the market-specific title for a secondary market', () => {
		render(
			<EditMarketModal
				market={ {
					id: 'be',
					label: 'Belgium',
					country: 'BE',
					countries: [ 'BE' ],
				} }
				onRequestClose={ () => {} }
			/>
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit Belgium' } )
		).toBeInTheDocument();
	} );

	test( 'renders AppSpinner inside the modal while data is loading', () => {
		const MarketFormMock = jest.requireMock( '../market-form' );
		MarketFormMock.mockImplementationOnce( () => <AppSpinner /> );

		render(
			<EditMarketModal market={ market } onRequestClose={ () => {} } />
		);

		expect(
			screen.getByRole( 'dialog', { name: 'Edit primary market' } )
		).toBeInTheDocument();
		expect( screen.getByRole( 'status' ) ).toBeInTheDocument();
	} );

	test( 'forwards the primary market countries from `market` unchanged (no target-audience override)', () => {
		render(
			<EditMarketModal
				market={ {
					id: 'primary',
					label: 'Primary Market',
					countries: [ 'US', 'CA' ],
				} }
				onRequestClose={ () => {} }
			/>
		);

		expect( MarketForm ).toHaveBeenCalledWith(
			expect.objectContaining( {
				initialMarket: expect.objectContaining( {
					id: 'primary',
					countries: [ 'US', 'CA' ],
				} ),
			} ),
			expect.anything()
		);
	} );

	test( 'preserves the secondary market countries', () => {
		render(
			<EditMarketModal
				market={ {
					id: 'fr',
					label: 'France',
					country: 'FR',
					countries: [ 'FR' ],
				} }
				onRequestClose={ () => {} }
			/>
		);

		expect( MarketForm ).toHaveBeenCalledWith(
			expect.objectContaining( {
				initialMarket: expect.objectContaining( {
					id: 'fr',
					country: 'FR',
					countries: [ 'FR' ],
				} ),
			} ),
			expect.anything()
		);
	} );

	test( 'invokes onCancel when the footer Cancel button is clicked', async () => {
		const user = userEvent.setup();
		const onCancel = jest.fn();
		render(
			<EditMarketModal market={ market } onRequestClose={ onCancel } />
		);

		// `getByRole('button', { name: 'Cancel' })` matches both the
		// `<Modal>`'s X button (aria-label) and the footer button. Use the
		// `is-tertiary` variant class to target only our footer button.
		const footerCancelButton = document.querySelector(
			'.app-modal__footer .is-tertiary'
		);
		await user.click( footerCancelButton );

		expect( onCancel ).toHaveBeenCalledTimes( 1 );
	} );
} );

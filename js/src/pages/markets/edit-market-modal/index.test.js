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
import MarketForm from '../market-form';
import EditMarketModal from './';

// MarketForm pulls in useSaveShippingRates → useSelect( STORE_KEY ) which
// requires the wc/gla store to be registered. Mock it to avoid that dependency.
jest.mock( '../market-form', () =>
	jest.fn( ( { children } ) =>
		children( {
			adapter: { isSaving: false },
			isValidForm: true,
			handleSubmit: jest.fn(),
			isDirty: false,
		} )
	)
);

// MarketFields requires AdaptiveForm context (provided by MarketForm). Mock it
// so it renders without that context since MarketForm itself is mocked above.
jest.mock( '../market-fields', () => jest.fn( () => null ) );

jest.mock( '~/hooks/useSettings' );

const market = { id: 'primary', label: 'Primary Market' };
const targetAudience = { countries: [ 'US' ] };

describe( 'EditMarketModal', () => {
	beforeEach( () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: 'manual' },
		} );
		MarketForm.mockClear();
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

	test( 'forwards target-audience countries as initialMarket.countries for the primary market', () => {
		render(
			<EditMarketModal
				market={ { id: 'primary', label: 'Primary Market' } }
				targetAudience={ { countries: [ 'US', 'CA', 'MX' ] } }
				onRequestClose={ () => {} }
			/>
		);

		expect( MarketForm ).toHaveBeenCalledWith(
			expect.objectContaining( {
				initialMarket: expect.objectContaining( {
					id: 'primary',
					countries: [ 'US', 'CA', 'MX' ],
				} ),
			} ),
			expect.anything()
		);
	} );

	test( 'preserves the secondary market countries (does not override with primary audience)', () => {
		render(
			<EditMarketModal
				market={ {
					id: 'fr',
					label: 'France',
					country: 'FR',
					countries: [ 'FR' ],
				} }
				targetAudience={ { countries: [ 'US', 'CA', 'MX' ] } }
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

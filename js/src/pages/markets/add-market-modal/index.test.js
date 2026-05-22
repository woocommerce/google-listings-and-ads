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
import useStoreCurrency from '~/hooks/useStoreCurrency';
import { SHIPPING_RATE_METHOD } from '~/constants';

jest.mock( '~/hooks/useSettings' );
jest.mock( '~/hooks/useStoreCurrency' );

// MarketForm pulls in useAppDispatch, useSaveShippingRates, useSaveShippingTimes.
// Mock it to a thin wrapper that calls its render-prop child with a minimal form context.
jest.mock( '../market-form', () =>
	jest.fn( ( { children } ) =>
		children( {
			adapter: { isSaving: false },
			isValidForm: true,
			handleSubmit: jest.fn(),
		} )
	)
);

// MarketFields requires AdaptiveForm context (provided by MarketForm). Mock it
// so it renders without that context since MarketForm itself is mocked above.
jest.mock( '../market-fields', () => jest.fn( () => null ) );

const defaultProps = {
	targetAudience: { countries: [], language: 'en' },
	settings: { shipping_rate: SHIPPING_RATE_METHOD.MANUAL },
	onRequestClose: jest.fn(),
};

describe( 'AddMarketModal', () => {
	beforeEach( () => {
		global.glaData.isMultiLingualStore = false;
		// MultiLingualPluginPrompt and LocaleSection read useSettings() directly.
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.MANUAL },
		} );
		useStoreCurrency.mockReturnValue( { code: 'USD' } );
	} );

	afterEach( () => {
		delete global.glaData.isMultiLingualStore;
	} );

	test( 'renders the modal with "Add market" title', () => {
		render( <AddMarketModal { ...defaultProps } /> );

		expect(
			screen.getByRole( 'dialog', { name: 'Add market' } )
		).toBeInTheDocument();
	} );

	test( 'invokes onRequestClose when the Cancel button is clicked', async () => {
		const user = userEvent.setup();
		const onRequestClose = jest.fn();

		render(
			<AddMarketModal
				{ ...defaultProps }
				onRequestClose={ onRequestClose }
			/>
		);

		await user.click( screen.getByRole( 'button', { name: 'Cancel' } ) );

		expect( onRequestClose ).toHaveBeenCalledTimes( 1 );
	} );

	test( 'does not show the "Add market" button when shipping_rate is MANUAL', () => {
		render( <AddMarketModal { ...defaultProps } /> );

		expect(
			screen.queryByRole( 'button', { name: 'Add market' } )
		).not.toBeInTheDocument();
	} );

	test( 'shows the "Add market" button when shipping_rate is not MANUAL', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.FLAT },
		} );
		render(
			<AddMarketModal
				{ ...defaultProps }
				settings={ { shipping_rate: SHIPPING_RATE_METHOD.FLAT } }
			/>
		);

		expect(
			screen.getByRole( 'button', { name: 'Add market' } )
		).toBeInTheDocument();
	} );

	test( 'shows the multilingual plugin prompt when store is not multilingual and shipping_rate is MANUAL', () => {
		render( <AddMarketModal { ...defaultProps } /> );

		expect(
			screen.getByText( 'Install a multilingual plugin to add markets' )
		).toBeInTheDocument();
		expect( screen.getByText( 'WPML' ) ).toBeInTheDocument();
		expect(
			screen.getByText(
				'WooCommerce integration that handles multi-currency natively.'
			)
		).toBeInTheDocument();
		expect(
			screen.getByRole( 'link', { name: 'Learn more' } )
		).toBeInTheDocument();
	} );

	test( 'hides the multilingual plugin prompt when the store already has a multilingual plugin', () => {
		global.glaData.isMultiLingualStore = true;

		render( <AddMarketModal { ...defaultProps } /> );

		expect(
			screen.queryByText( 'Install a multilingual plugin to add markets' )
		).not.toBeInTheDocument();
	} );

	test( 'hides the multilingual plugin prompt when shipping_rate is not MANUAL', () => {
		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.FLAT },
		} );
		render(
			<AddMarketModal
				{ ...defaultProps }
				settings={ { shipping_rate: SHIPPING_RATE_METHOD.FLAT } }
			/>
		);

		expect(
			screen.queryByText( 'Install a multilingual plugin to add markets' )
		).not.toBeInTheDocument();
	} );

	test( 'calls showValidation and not handleSubmit when the form is invalid and "Add market" is clicked', async () => {
		const user = userEvent.setup();
		const showValidation = jest.fn();
		const handleSubmit = jest.fn();

		const MarketForm = jest.requireMock( '../market-form' );
		MarketForm.mockImplementationOnce( ( { children } ) =>
			children( {
				adapter: { isSaving: false, showValidation },
				isValidForm: false,
				handleSubmit,
			} )
		);

		useSettings.mockReturnValue( {
			settings: { shipping_rate: SHIPPING_RATE_METHOD.FLAT },
		} );
		render(
			<AddMarketModal
				{ ...defaultProps }
				settings={ { shipping_rate: SHIPPING_RATE_METHOD.FLAT } }
			/>
		);

		await user.click(
			screen.getByRole( 'button', { name: 'Add market' } )
		);

		expect( showValidation ).toHaveBeenCalledTimes( 1 );
		expect( handleSubmit ).not.toHaveBeenCalled();
	} );

	describe( 'automatic non-multilingual scenario', () => {
		const automaticSettings = {
			shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC,
		};

		beforeEach( () => {
			global.glaData.isMultiLingualStore = false;
			useSettings.mockReturnValue( { settings: automaticSettings } );
		} );

		test( 'shows the "Add market" button', () => {
			render(
				<AddMarketModal
					{ ...defaultProps }
					settings={ automaticSettings }
				/>
			);

			expect(
				screen.getByRole( 'button', { name: 'Add market' } )
			).toBeInTheDocument();
		} );

		test( 'does not render the multilingual plugin prompt', () => {
			render(
				<AddMarketModal
					{ ...defaultProps }
					settings={ automaticSettings }
				/>
			);

			expect(
				screen.queryByText(
					'Install a multilingual plugin to add markets'
				)
			).not.toBeInTheDocument();
		} );
	} );
} );

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
jest.mock( '~/hooks/useMCSupportedLanguages', () =>
	jest.fn( () => ( { languages: [], hasFinishedResolution: true } ) )
);

// Prevent useAdaptiveFormContext from throwing when rendered outside its real provider.
jest.mock( '~/components/adaptive-form', () => ( {
	useAdaptiveFormContext: jest.fn( () => ( {
		adapter: { isEditing: false, isPrimaryMarket: false },
		getInputProps: jest.fn( () => ( {
			value: '',
			onChange: jest.fn(),
			onBlur: jest.fn(),
		} ) ),
	} ) ),
	useAdaptiveFormInputProps: jest.fn(),
} ) );

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

// AudienceSection and ShippingSection have complex form dependencies; mock them
// so tests focus on the modal's own behaviour.
jest.mock( '../market-fields/audience-section', () => jest.fn( () => null ) );
jest.mock( '../market-fields/shipping-section', () => jest.fn( () => null ) );

const defaultProps = {
	targetAudience: { countries: [], language: 'en' },
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
		render( <AddMarketModal { ...defaultProps } /> );

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
		render( <AddMarketModal { ...defaultProps } /> );

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
		render( <AddMarketModal { ...defaultProps } /> );

		await user.click(
			screen.getByRole( 'button', { name: 'Add market' } )
		);

		expect( showValidation ).toHaveBeenCalledTimes( 1 );
		expect( handleSubmit ).not.toHaveBeenCalled();
	} );

	describe( 'automatic non-multilingual scenario', () => {
		beforeEach( () => {
			global.glaData.isMultiLingualStore = false;
			useSettings.mockReturnValue( {
				settings: { shipping_rate: SHIPPING_RATE_METHOD.AUTOMATIC },
			} );
		} );

		test( 'shows the "Add market" button', () => {
			render( <AddMarketModal { ...defaultProps } /> );

			expect(
				screen.getByRole( 'button', { name: 'Add market' } )
			).toBeInTheDocument();
		} );

		test( 'does not render the multilingual plugin prompt', () => {
			render( <AddMarketModal { ...defaultProps } /> );

			expect(
				screen.queryByText(
					'Install a multilingual plugin to add markets'
				)
			).not.toBeInTheDocument();
		} );

		test( 'renders a disabled Language field with "Requires multilingual plugin" placeholder', () => {
			render( <AddMarketModal { ...defaultProps } /> );

			const input = screen.getByRole( 'textbox', { name: 'Language' } );
			expect( input ).toBeDisabled();
			expect( input ).toHaveAttribute(
				'placeholder',
				'Requires multilingual plugin'
			);
		} );

		test( 'renders a disabled Currency field with "Requires multilingual plugin" placeholder', () => {
			render( <AddMarketModal { ...defaultProps } /> );

			const input = screen.getByRole( 'textbox', { name: 'Currency' } );
			expect( input ).toBeDisabled();
			expect( input ).toHaveAttribute(
				'placeholder',
				'Requires multilingual plugin'
			);
		} );

		test( 'disables the "Add market" button when the form is invalid', () => {
			const MarketForm = jest.requireMock( '../market-form' );
			MarketForm.mockImplementationOnce( ( { children } ) =>
				children( {
					adapter: { isSaving: false },
					isValidForm: false,
					handleSubmit: jest.fn(),
				} )
			);

			render( <AddMarketModal { ...defaultProps } /> );

			expect(
				screen.getByRole( 'button', { name: 'Add market' } )
			).toBeDisabled();
		} );
	} );
} );
